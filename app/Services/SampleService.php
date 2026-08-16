<?php

namespace App\Services;

use App\Models\Sample;
use App\Models\TestRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SampleService
{
    private const NEXT_STATUS = [
        'registered' => 'collected',
        'collected' => 'in_progress',
        'in_progress' => 'completed',
    ];

    public function __construct(
        protected AuditLogService $auditLogService,
        protected InventoryService $inventoryService
    ) {
    }

    public function enterResult(Sample $sample): Sample
    {
        return $sample->load([
            'testRequest.patient',
            'testRequest.items.test',
        ]);
    }

    public function register(array $data): Sample
    {
        return DB::transaction(function () use ($data) {
            $testRequest = TestRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($data['test_request_id']);

            if (in_array($testRequest->status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'test_request_id' => [
                        'A sample cannot be registered for a completed or cancelled request.',
                    ],
                ]);
            }

            if ($testRequest->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'test_request_id' => [
                        'The selected request does not contain any tests.',
                    ],
                ]);
            }

            $hasActiveSample = $testRequest->samples()
                ->whereNotIn('status', ['rejected', 'cancelled'])
                ->exists();

            if ($hasActiveSample) {
                throw ValidationException::withMessages([
                    'test_request_id' => [
                        'This request already has an active sample. Reject or cancel it before registering a replacement.',
                    ],
                ]);
            }

            $identifiers = $this->generateIdentifiers();

            $sample = Sample::create([
                'test_request_id' => $testRequest->id,
                'sample_type' => $data['sample_type'],
                'sample_number' => $identifiers['sample_number'],
                'barcode' => $identifiers['barcode'],
                'qr_code' => $identifiers['qr_code'],
                'status' => 'registered',
            ]);

            $this->recordHistory($sample, null, 'registered');

            if ($testRequest->status === 'pending') {
                $testRequest->update(['status' => 'processing']);
            }

            $this->auditLogService->record(
                'Sample',
                $sample->id,
                'registered',
                null,
                [
                    'sample_number' => $sample->sample_number,
                    'test_request_id' => $sample->test_request_id,
                    'sample_type' => $sample->sample_type,
                    'status' => $sample->status,
                ]
            );

            return $this->getSample($sample->id);
        });
    }

    private function generateIdentifiers(): array
    {
        do {
            $identifier = now()->format('ymd') . '-' . Str::upper(Str::random(8));
            $sampleNumber = 'SMP-' . $identifier;
        } while (Sample::where('sample_number', $sampleNumber)->exists());

        return [
            'sample_number' => $sampleNumber,
            'barcode' => 'BC-' . $identifier,
            'qr_code' => 'LIMS:SAMPLE:' . $sampleNumber,
        ];
    }

    public function getSamples(array $data): LengthAwarePaginator
    {
        $search = $data['search'] ?? null;
        $status = $data['status'] ?? null;
        $testRequestId = $data['test_request_id'] ?? null;
        $perPage = $data['per_page'] ?? 15;

        return Sample::query()
            ->with([
                'testRequest.patient',
                'testRequest.items.test',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('sample_number', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('qr_code', 'like', "%{$search}%")
                        ->orWhereHas('testRequest', function ($query) use ($search) {
                            $query
                                ->where('request_number', 'like', "%{$search}%")
                                ->orWhereHas('patient', function ($query) use ($search) {
                                    $query
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('patient_number', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->when(
                $testRequestId,
                fn($query) => $query->where('test_request_id', $testRequestId)
            )
            ->latest()
            ->paginate($perPage);
    }

    public function getSample(string $id): Sample
    {
        return Sample::query()
            ->with([
                'testRequest.patient',
                'testRequest.items.test',
                'results',
                'statusHistories.changedBy',
                'reagentConsumptions.reagent',
                'reagentConsumptions.lot',
                'reagentConsumptions.testRequestItem.test',
            ])
            ->findOrFail($id);
    }

    public function updateStatus(Sample $sample, string $status): Sample
    {
        return DB::transaction(function () use ($sample, $status) {
            $sample = Sample::query()->lockForUpdate()->findOrFail($sample->id);
            $expectedStatus = self::NEXT_STATUS[$sample->status] ?? null;

            if ($status !== $expectedStatus) {
                throw ValidationException::withMessages([
                    'status' => [
                        $expectedStatus
                            ? "The next allowed status is {$expectedStatus}."
                            : 'This sample is already in a terminal status.',
                    ],
                ]);
            }

            $fromStatus = $sample->status;
            $changes = ['status' => $status];

            if ($status === 'collected') {
                $changes['collected_at'] = now();
                $changes['received_at'] = $changes['collected_at'];
            }


            if ($status === 'in_progress') {
                $this->inventoryService->consumeForSample($sample);
                $changes['reagents_consumed_at'] = $sample->fresh()->reagents_consumed_at;
            }

            $sample->update($changes);
            $this->recordHistory($sample, $fromStatus, $status);

            $this->auditLogService->record(
                'Sample',
                $sample->id,
                'status_updated',
                ['status' => $fromStatus],
                ['status' => $status]
            );

            return $this->getSample($sample->id);
        });
    }

    public function track(string $code): Sample
    {
        return Sample::query()
            ->with([
                'testRequest.patient',
                'testRequest.items.test',
                'results',
                'statusHistories.changedBy',
                'reagentConsumptions.reagent',
                'reagentConsumptions.lot',
                'reagentConsumptions.testRequestItem.test',
            ])
            ->where(function ($query) use ($code) {
                $query
                    ->where('sample_number', $code)
                    ->orWhere('barcode', $code)
                    ->orWhere('qr_code', $code);
            })
            ->firstOrFail();
    }

    public function reject(Sample $sample, string $reason): Sample
    {
        return $this->setDisposition(
            $sample,
            'rejected',
            $reason,
            ['registered', 'collected', 'in_progress']
        );
    }

    public function cancel(Sample $sample, string $reason): Sample
    {
        return $this->setDisposition(
            $sample,
            'cancelled',
            $reason,
            ['registered', 'collected']
        );
    }

    private function setDisposition(
        Sample $sample,
        string $status,
        string $reason,
        array $allowedFrom
    ): Sample {
        return DB::transaction(function () use ($sample, $status, $reason, $allowedFrom) {
            $sample = Sample::query()->lockForUpdate()->findOrFail($sample->id);

            if (!in_array($sample->status, $allowedFrom, true)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "A {$sample->status} sample cannot be {$status}.",
                    ],
                ]);
            }

            $fromStatus = $sample->status;
            $reasonField = $status === 'rejected'
                ? 'rejected_reason'
                : 'cancelled_reason';

            $sample->update([
                'status' => $status,
                $reasonField => $reason,
            ]);

            $this->recordHistory($sample, $fromStatus, $status, $reason);

            $this->auditLogService->record(
                'Sample',
                $sample->id,
                $status,
                ['status' => $fromStatus],
                ['status' => $status],
                $reason
            );

            return $this->getSample($sample->id);
        });
    }

    private function recordHistory(
        Sample $sample,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null
    ): void {
        $sample->statusHistories()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'changed_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $samples = Sample::query()
            ->with([
                'testRequest.patient',
                'testRequest.items.test',
            ])
            ->get();

        return response()->streamDownload(function () use ($samples) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Sample Number',
                'Barcode',
                'Patient',
                'Request',
                'Tests',
                'Sample Type',
                'Status',
                'Created At',
            ]);

            foreach ($samples as $sample) {
                fputcsv($handle, [
                    $sample->sample_number,
                    $sample->barcode,
                    $sample->testRequest?->patient?->name,
                    $sample->testRequest?->request_number,
                    $sample->testRequest?->items->pluck('test.name')->filter()->implode(', '),
                    $sample->sample_type,
                    $sample->status,
                    $sample->created_at,
                ]);
            }

            fclose($handle);
        }, 'samples.csv', ['Content-Type' => 'text/csv']);
    }

    public function destroy(Sample $sample): void
    {
        DB::transaction(function () use ($sample) {
            $sample = Sample::query()
                ->withCount('results')
                ->lockForUpdate()
                ->findOrFail($sample->id);

            if ($sample->status !== 'registered' || $sample->results_count > 0) {
                throw ValidationException::withMessages([
                    'sample' => [
                        'Only a newly registered sample without results can be deleted.',
                    ],
                ]);
            }

            $oldValues = [
                'sample_number' => $sample->sample_number,
                'status' => $sample->status,
                'test_request_id' => $sample->test_request_id,
            ];

            $testRequestId = $sample->test_request_id;

            $sample->delete();

            $testRequest = TestRequest::query()
                ->lockForUpdate()
                ->find($testRequestId);

            if (
                $testRequest?->status === 'processing'
                && !$testRequest->samples()->exists()
            ) {
                $testRequest->update(['status' => 'pending']);
            }

            $this->auditLogService->record(
                'Sample',
                $sample->id,
                'deleted',
                $oldValues
            );
        });
    }
}
