<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Test;
use App\Models\TestRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Invoice;

class TestRequestService
{
    public function __construct(
        protected InsuranceService $insuranceService
    ) {
    }

    public function getTestRequests(
        array $data
    ): LengthAwarePaginator {
        $search = $data['search'] ?? null;

        $patientId = $data['patient_id'] ?? null;

        $status = $data['status'] ?? null;

        $perPage = $data['per_page'] ?? 15;

        return TestRequest::query()
            ->with([
                'patient',
                'insuranceCompany',
                'items.test',
                'invoice',
            ])
            ->when(
                $search,
                function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where(
                            'request_number',
                            'like',
                            "%{$search}%"
                        )->orWhereHas(
                            'patient',
                            function ($query) use ($search) {
                                $query
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('patient_number', 'like', "%{$search}%");
                            }
                        );
                    });
                }
            )
            ->when(
                $patientId,
                fn($query) =>
                $query->where(
                    'patient_id',
                    $patientId
                )
            )
            ->when(
                $status,
                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            )
            ->latest()
            ->paginate($perPage);
    }


    public function getTestRequest(
        string $id
    ): TestRequest {
        return TestRequest::query()
            ->with([
                'patient',
                'insuranceCompany',
                'items.test',
                'invoice',
                'samples',
            ])
            ->findOrFail($id);
    }

    public function createTestRequest(
        array $data
    ): TestRequest {
        return DB::transaction(function () use ($data) {

            $tests = $this->getTests(
                $data['tests']
            );

            $totalPrice = $this->calculateTotal(
                $tests,
                $data['tests']
            );

            $requestNumber = $this->nextRequestNumber();

            $testRequest = TestRequest::create([

                'request_number' => $requestNumber,

                'patient_id' => $data['patient_id'],

                'insurance_company_id' =>
                    $data['insurance_company_id'] ?? null,

                'status' => 'pending',

                'total_price' => $totalPrice,

            ]);

            $this->createItems(
                $testRequest,
                $tests,
                $data['tests']
            );

            $invoice = Invoice::create([

                'test_request_id' => $testRequest->id,

                'total' => $totalPrice,

                'insurance_amount' => 0,

                'patient_due' => $totalPrice,

                'paid' => 0,

                'remaining' => $totalPrice,

                'status' => 'pending',

            ]);

            $this->insuranceService->syncInvoiceCoverage(
                $testRequest,
                $invoice
            );

            return $this->getTestRequest(
                $testRequest->id
            );
        });
    }

    public function updateTestRequest(
        TestRequest $testRequest,
        array $data
    ): TestRequest {
        return DB::transaction(
            function () use ($testRequest, $data) {

                $reason = $data['reason'];
                unset($data['reason']);

                if (
                    $testRequest->status === 'completed'
                    || $testRequest->status === 'cancelled'
                ) {
                    throw ValidationException::withMessages([
                        'test_request' => [
                            'Completed or cancelled test requests cannot be updated.',
                        ],
                    ]);
                }

                $changesCoreData = isset($data['patient_id'])
                    || array_key_exists('insurance_company_id', $data)
                    || isset($data['tests']);

                if ($changesCoreData && $testRequest->samples()->exists()) {
                    throw ValidationException::withMessages([
                        'test_request' => [
                            'Patient, insurance, and tests cannot be changed after samples are registered.',
                        ],
                    ]);
                }

                $invoice = $testRequest->invoice()->lockForUpdate()->first();

                if ($changesCoreData && $invoice && (float) $invoice->paid > 0) {
                    throw ValidationException::withMessages([
                        'test_request' => [
                            'Patient, insurance, and tests cannot be changed after a payment is recorded.',
                        ],
                    ]);
                }

                $old = [
                    'patient_id' => $testRequest->patient_id,
                    'insurance_company_id' => $testRequest->insurance_company_id,
                    'status' => $testRequest->status,
                    'total_price' => $testRequest->total_price,
                    'tests' => $testRequest->items()
                        ->get(['test_id', 'quantity', 'price'])
                        ->toArray(),
                ];

                $testRequest->update([
                    'patient_id' =>
                        $data['patient_id']
                        ?? $testRequest->patient_id,

                    'insurance_company_id' =>
                        array_key_exists(
                            'insurance_company_id',
                            $data
                        )
                        ? $data['insurance_company_id']
                        : $testRequest
                            ->insurance_company_id,

                    'status' =>
                        $data['status']
                        ?? $testRequest->status,
                ]);

                if (isset($data['tests'])) {

                    $tests = $this->getTests(
                        $data['tests']
                    );

                    $totalPrice = $this->calculateTotal(
                        $tests,
                        $data['tests']
                    );

                    $testRequest
                        ->items()
                        ->delete();

                    $this->createItems(
                        $testRequest,
                        $tests,
                        $data['tests']
                    );

                    $testRequest->update([
                        'total_price' => $totalPrice,
                    ]);

                }

                if ($changesCoreData && $invoice) {
                    $this->insuranceService->syncInvoiceCoverage(
                        $testRequest->refresh(),
                        $invoice
                    );
                }

                $testRequest->refresh();

                $new = [
                    'patient_id' => $testRequest->patient_id,
                    'insurance_company_id' => $testRequest->insurance_company_id,
                    'status' => $testRequest->status,
                    'total_price' => $testRequest->total_price,
                    'tests' => $testRequest->items()
                        ->get(['test_id', 'quantity', 'price'])
                        ->toArray(),
                ];

                if (Auth::check()) {
                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'entity_type' => 'TestRequest',
                        'entity_id' => $testRequest->id,
                        'action' => 'update',
                        'old_values' => $old,
                        'new_values' => $new,
                        'reason' => $reason,
                        'ip_address' => request()->ip(),
                        'timestamp' => now(),
                    ]);
                }

                return $this->getTestRequest(
                    $testRequest->id
                );
            }
        );
    }

    private function getTests(
        array $requestTests
    ) {
        $testIds = collect($requestTests)
            ->pluck('test_id')
            ->all();

        $tests = Test::query()
            ->whereIn('id', $testIds)
            ->get()
            ->keyBy('id');

        if ($tests->count() !== count($testIds)) {
            throw ValidationException::withMessages([
                'tests' => [
                    'One or more selected tests do not exist.',
                ],
            ]);
        }

        return $tests;
    }


    private function calculateTotal(
        $tests,
        array $requestTests
    ): float {
        return collect($requestTests)
            ->sum(function ($requestTest) use ($tests) {

                $test = $tests->get(
                    $requestTest['test_id']
                );

                return (float) $test->price
                    * (int) $requestTest['quantity'];
            });
    }



    private function createItems(
        TestRequest $testRequest,
        $tests,
        array $requestTests
    ): void {
        foreach ($requestTests as $requestTest) {

            $test = $tests->get(
                $requestTest['test_id']
            );

            $testRequest->items()->create([
                'test_id' => $test->id,

                'quantity' =>
                    $requestTest['quantity'],

                'price' => $test->price,
            ]);
        }
    }

    private function nextRequestNumber(): string
    {
        $lastNumber = TestRequest::query()
            ->lockForUpdate()
            ->orderByRaw(
                "CAST(SUBSTRING(request_number, 5) AS UNSIGNED) DESC"
            )
            ->value('request_number');

        $next = $lastNumber
            ? ((int) str_replace('REQ-', '', $lastNumber)) + 1
            : 1001;

        return 'REQ-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function exportCsv(): StreamedResponse
    {

        $requests = TestRequest::query()

            ->with([

                'patient',

                'items.test',

                'invoice',

            ])

            ->get();

        return response()->streamDownload(

            function () use ($requests) {

                $handle = fopen(
                    'php://output',
                    'w'
                );

                fputcsv($handle, [

                    'Request Number',

                    'Patient',

                    'Tests',

                    'Total',

                    'Patient Due',

                    'Status',

                    'Created At'

                ]);

                foreach ($requests as $request) {

                    fputcsv($handle, [

                        $request->request_number,

                        $request->patient?->name,

                        $request->items
                            ->pluck('test.name')
                            ->implode(', '),

                        $request->total_price,

                        $request->invoice?->remaining ?? 0,

                        $request->status,

                        $request->created_at,

                    ]);

                }

                fclose($handle);

            },

            'test_requests.csv',

            [

                'Content-Type' => 'text/csv'

            ]

        );

    }

    public function destroy(
        TestRequest $testRequest
    ): void {

        DB::transaction(

            function () use ($testRequest) {

                $testRequest->loadMissing([
                    'samples',
                    'medicalReport',
                    'invoice.payments',
                    'invoice.refunds',
                ]);

                if (
                    $testRequest->samples->isNotEmpty()
                    || $testRequest->medicalReport
                    || $testRequest->invoice?->payments->isNotEmpty()
                    || $testRequest->invoice?->refunds->isNotEmpty()
                ) {
                    throw ValidationException::withMessages([
                        'test_request' => [
                            'This request has clinical or financial records and cannot be deleted.',
                        ],
                    ]);
                }

                $testRequest
                    ->items()
                    ->delete();

                $testRequest
                    ->delete();

            }

        );

    }
}
