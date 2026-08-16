<?php

namespace App\Services;

use App\Models\Sample;
use App\Models\Test;
use App\Models\TestResult;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestResultService
{
    private const EDITABLE_STATUSES = ['draft', 'correction_required'];

    public function __construct(
        protected AuditLogService $auditLogService,
        protected MedicalReportService $medicalReportService,
        protected NotificationService $notificationService
    ) {
    }

    public function getResults(array $filters = []): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;

        return TestResult::query()
            ->with($this->resultRelations())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('result_number', 'like', "%{$search}%")
                        ->orWhere('value', 'like', "%{$search}%")
                        ->orWhereHas('sample', fn($query) => $query
                            ->where('sample_number', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%"))
                        ->orWhereHas('sample.testRequest.patient', fn($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%"))
                        ->orWhereHas('testRequestItem.test', fn($query) => $query
                            ->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn($query) => $query->where('workflow_status', $status))
            ->latest('updated_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getResult(TestResult $result): TestResult
    {
        return $result->load($this->resultRelations(includeTimeline: true));
    }

    public function getEntryWorkspace(Sample $sample): Sample
    {
        return $sample->load([
            'testRequest.patient',
            'testRequest.items.test',
            'results' => fn($query) => $query->with($this->resultRelations(includeSample: false)),
        ]);
    }

    public function saveSampleResults(Sample $sample, array $data, User $user): array
    {
        return DB::transaction(function () use ($sample, $data, $user) {
            $sample = Sample::query()
                ->with('testRequest.items.test')
                ->lockForUpdate()
                ->findOrFail($sample->id);

            $this->assertSampleAcceptsResults($sample);
            $items = $sample->testRequest->items->keyBy('id');
            $saved = [];

            foreach ($data['results'] as $input) {
                $item = $items->get($input['test_request_item_id']);

                if (!$item) {
                    throw ValidationException::withMessages([
                        'results' => ['Every result must belong to the sample test request.'],
                    ]);
                }

                $result = TestResult::query()
                    ->where('sample_id', $sample->id)
                    ->where('test_request_item_id', $item->id)
                    ->lockForUpdate()
                    ->first();

                if ($result && !in_array($result->workflow_status, self::EDITABLE_STATUSES, true)) {
                    throw ValidationException::withMessages([
                        'results' => ["{$item->test->name} cannot be edited while it is {$result->workflow_status}."],
                    ]);
                }

                $old = $result?->only(['value', 'flag', 'notes', 'workflow_status']);
                $value = trim($input['value']);
                $this->validateValue($item->test, $value);
                $flag = $this->calculateFlag($item->test, $value);

                if (!$result) {
                    $result = new TestResult([
                        'sample_id' => $sample->id,
                        'test_request_item_id' => $item->id,
                    ]);
                }

                $fromStatus = $result->exists ? $result->workflow_status : null;
                $result->fill([
                    'value' => $value,
                    'value_unit' => $item->test->unit,
                    'reference_range' => $item->test->reference_range,
                    'flag' => $flag,
                    'status' => 'draft',
                    'workflow_status' => 'draft',
                    'notes' => $input['notes'] ?? null,
                    'entered_by' => $user->id,
                    'entered_at' => now(),
                    'submitted_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_notes' => null,
                    'approved' => false,
                    'approved_by' => null,
                    'approved_at' => null,
                ])->save();

                if ($fromStatus !== 'draft') {
                    $this->recordHistory(
                        $result,
                        $fromStatus,
                        'draft',
                        $fromStatus === 'correction_required'
                            ? 'Corrected result saved as a new draft.'
                            : 'Result draft created.',
                        $user
                    );
                }

                $this->auditLogService->record(
                    'TestResult',
                    $result->id,
                    $old ? 'draft_updated' : 'draft_created',
                    $old,
                    $result->only(['value', 'flag', 'notes', 'workflow_status'])
                );

                $saved[] = $this->getResult($result);
            }

            return $saved;
        });
    }

    public function submitSampleResults(Sample $sample, User $user): array
    {
        return DB::transaction(function () use ($sample, $user) {
            $sample = Sample::query()
                ->with(['testRequest.items.test', 'results'])
                ->lockForUpdate()
                ->findOrFail($sample->id);

            if ($sample->status !== 'completed') {
                throw ValidationException::withMessages([
                    'sample' => ['Complete the sample analysis before submitting its results for review.'],
                ]);
            }

            $results = $sample->results->keyBy('test_request_item_id');
            $missing = $sample->testRequest->items
                ->filter(fn($item) => !$results->has($item->id))
                ->map(fn($item) => $item->test?->name ?? $item->id)
                ->values();

            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'results' => ['Enter every requested result before submission: ' . $missing->join(', ') . '.'],
                ]);
            }

            $submitted = 0;

            foreach ($results as $result) {
                if (!in_array($result->workflow_status, self::EDITABLE_STATUSES, true)) {
                    continue;
                }

                $fromStatus = $result->workflow_status;
                $result->update([
                    'workflow_status' => 'pending_review',
                    'submitted_at' => now(),
                    'correction_reason' => null,
                ]);
                $this->recordHistory($result, $fromStatus, 'pending_review', null, $user);
                $this->auditLogService->record(
                    'TestResult',
                    $result->id,
                    'submitted_for_review',
                    ['workflow_status' => $fromStatus],
                    ['workflow_status' => 'pending_review']
                );
                $submitted++;
            }

            if ($submitted === 0) {
                throw ValidationException::withMessages([
                    'results' => ['There are no draft or corrected results to submit.'],
                ]);
            }

            return $results->map(fn($result) => $this->getResult($result->refresh()))->values()->all();
        });
    }

    public function review(TestResult $result, ?string $notes, User $user): TestResult
    {
        $this->assertAdmin($user);

        return $this->transition($result, 'pending_review', 'reviewed', $user, $notes, [
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function returnForCorrection(TestResult $result, string $reason, User $user): TestResult
    {
        $this->assertAdmin($user);

        return DB::transaction(function () use ($result, $reason, $user) {
            $result = TestResult::query()->lockForUpdate()->findOrFail($result->id);

            if (!in_array($result->workflow_status, ['pending_review', 'reviewed'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Only a pending or reviewed result can be returned for correction.'],
                ]);
            }

            $fromStatus = $result->workflow_status;
            $result->update([
                'workflow_status' => 'correction_required',
                'correction_reason' => $reason,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            $this->recordHistory($result, $fromStatus, 'correction_required', $reason, $user);
            $this->auditLogService->record(
                'TestResult',
                $result->id,
                'returned_for_correction',
                ['workflow_status' => $fromStatus],
                ['workflow_status' => 'correction_required'],
                $reason
            );

            return $this->getResult($result->refresh());
        });
    }

    public function approve(TestResult $result, User $user): TestResult
    {
        $this->assertAdmin($user);

        return DB::transaction(function () use ($result, $user) {
            $result = TestResult::query()->lockForUpdate()->findOrFail($result->id);

            if ($result->workflow_status !== 'reviewed') {
                throw ValidationException::withMessages([
                    'status' => ['A result must be medically reviewed before it can be approved.'],
                ]);
            }

            $result->update([
                'workflow_status' => 'approved',
                'status' => 'completed',
                'approved' => true,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            $this->recordHistory($result, 'reviewed', 'approved', null, $user);
            $this->auditLogService->record(
                'TestResult',
                $result->id,
                'approved',
                ['workflow_status' => 'reviewed', 'approved' => false],
                ['workflow_status' => 'approved', 'approved' => true]
            );

            $this->completeRequestIfReady($result);

            return $this->getResult($result->refresh());
        });
    }

    public function allBySample(Sample $sample)
    {
        return TestResult::query()
            ->where('sample_id', $sample->id)
            ->with($this->resultRelations(includeTimeline: true))
            ->get();
    }

    public function exportCsv(): StreamedResponse
    {
        $results = TestResult::query()->with($this->resultRelations())->get();

        return response()->streamDownload(function () use ($results) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Result Number', 'Patient', 'Request', 'Sample', 'Test', 'Value',
                'Unit', 'Reference', 'Flag', 'Workflow Status', 'Entered At',
                'Reviewed At', 'Approved At',
            ]);

            foreach ($results as $result) {
                fputcsv($handle, [
                    $result->result_number,
                    $result->sample?->testRequest?->patient?->name,
                    $result->sample?->testRequest?->request_number,
                    $result->sample?->sample_number,
                    $result->testRequestItem?->test?->name,
                    $result->value,
                    $result->value_unit,
                    $result->reference_range,
                    $result->flag,
                    $result->workflow_status,
                    $result->entered_at,
                    $result->reviewed_at,
                    $result->approved_at,
                ]);
            }

            fclose($handle);
        }, 'test-results.csv', ['Content-Type' => 'text/csv']);
    }

    private function transition(
        TestResult $result,
        string $expected,
        string $next,
        User $user,
        ?string $reason,
        array $changes
    ): TestResult {
        return DB::transaction(function () use ($result, $expected, $next, $user, $reason, $changes) {
            $result = TestResult::query()->lockForUpdate()->findOrFail($result->id);

            if ($result->workflow_status !== $expected) {
                throw ValidationException::withMessages([
                    'status' => ["Only a {$expected} result can move to {$next}."],
                ]);
            }

            $result->update(['workflow_status' => $next] + $changes);
            $this->recordHistory($result, $expected, $next, $reason, $user);
            $this->auditLogService->record(
                'TestResult',
                $result->id,
                $next,
                ['workflow_status' => $expected],
                ['workflow_status' => $next],
                $reason
            );

            return $this->getResult($result->refresh());
        });
    }

    private function completeRequestIfReady(TestResult $result): void
    {
        $result->loadMissing('sample.testRequest.items', 'sample.testRequest.patient');
        $testRequest = $result->sample->testRequest;
        $itemIds = $testRequest->items->pluck('id');
        $approvedItemIds = TestResult::query()
            ->whereIn('test_request_item_id', $itemIds)
            ->where('workflow_status', 'approved')
            ->distinct()
            ->pluck('test_request_item_id');

        if ($itemIds->diff($approvedItemIds)->isNotEmpty()) {
            return;
        }

        $testRequest->update(['status' => 'completed']);
        $this->medicalReportService->generate($testRequest->fresh());
        DB::afterCommit(function () use ($testRequest) {
            try {
                $this->notificationService->notifyPatient(
                    $testRequest->patient,
                    "Your laboratory report {$testRequest->request_number} is ready."
                );
            } catch (\Throwable $exception) {
                Log::warning('The result was approved, but the patient notification could not be sent.', [
                    'test_request_id' => $testRequest->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    private function assertSampleAcceptsResults(Sample $sample): void
    {
        if (!in_array($sample->status, ['in_progress', 'completed'], true)) {
            throw ValidationException::withMessages([
                'sample' => ['Results can only be entered after analysis has started.'],
            ]);
        }

        if (in_array($sample->testRequest->status, ['cancelled'], true)) {
            throw ValidationException::withMessages([
                'sample' => ['Results cannot be entered for a cancelled request.'],
            ]);
        }
    }

    private function validateValue(Test $test, string $value): void
    {
        if ($value === '') {
            throw ValidationException::withMessages(['value' => ['A result value is required.']]);
        }

        if ($test->result_type === 'numeric' && !is_numeric($value)) {
            throw ValidationException::withMessages([
                'value' => ["{$test->name} requires a numeric result."],
            ]);
        }

        if (
            $test->result_type === 'choice'
            && !in_array($value, $test->result_options ?? [], true)
        ) {
            throw ValidationException::withMessages([
                'value' => ["Select a configured result for {$test->name}."],
            ]);
        }
    }

    private function calculateFlag(Test $test, string $value): string
    {
        if ($test->result_type === 'choice') {
            return strcasecmp($value, trim($test->reference_range)) === 0 ? 'normal' : 'high';
        }

        if ($test->result_type !== 'numeric' || !is_numeric($value)) {
            return 'normal';
        }

        $number = (float) $value;
        if ($test->critical_low !== null && $number < (float) $test->critical_low) {
            return 'critical';
        }
        if ($test->critical_high !== null && $number > (float) $test->critical_high) {
            return 'critical';
        }

        $reference = trim($test->reference_range);
        if (preg_match('/^(-?\d+(?:\.\d+)?)\s*-\s*(-?\d+(?:\.\d+)?)$/', $reference, $matches)) {
            if ($number < (float) $matches[1]) return 'low';
            if ($number > (float) $matches[2]) return 'high';
        }
        if (preg_match('/^<\s*(-?\d+(?:\.\d+)?)$/', $reference, $matches)) {
            return $number < (float) $matches[1] ? 'normal' : 'high';
        }
        if (preg_match('/^>\s*(-?\d+(?:\.\d+)?)$/', $reference, $matches)) {
            return $number > (float) $matches[1] ? 'normal' : 'low';
        }

        return 'normal';
    }

    private function recordHistory(
        TestResult $result,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason,
        User $user
    ): void {
        $result->statusHistories()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'changed_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    private function assertAdmin(User $user): void
    {
        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'role' => ['Only an administrator can review or approve results.'],
            ]);
        }
    }

    private function resultRelations(bool $includeTimeline = false, bool $includeSample = true): array
    {
        $relations = [
            'testRequestItem.test',
            'enteredBy',
            'reviewedBy',
            'approvedBy',
        ];

        if ($includeSample) {
            $relations[] = 'sample.testRequest.patient';
            $relations[] = 'sample.testRequest.medicalReport';
        }
        if ($includeTimeline) {
            $relations[] = 'statusHistories.changedBy';
        }

        return $relations;
    }
}
