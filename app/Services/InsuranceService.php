<?php

namespace App\Services;

use App\Models\CoverageRule;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\TestRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InsuranceService
{
    public function applyInsurance(
        TestRequest $testRequest,
        array $data
    ): array {
        return DB::transaction(function () use ($testRequest, $data) {
            $testRequest = TestRequest::query()
                ->lockForUpdate()
                ->findOrFail($testRequest->id);

            if ($testRequest->samples()->exists()) {
                throw ValidationException::withMessages([
                    'insurance_company_id' => [
                        'Insurance cannot be changed after a sample is registered.',
                    ],
                ]);
            }

            $invoice = $testRequest->invoice()->lockForUpdate()->firstOrFail();

            if ((float) $invoice->paid > 0 || $invoice->payments()->exists()) {
                throw ValidationException::withMessages([
                    'insurance_company_id' => [
                        'Insurance cannot be changed after payment is recorded.',
                    ],
                ]);
            }

            $company = InsuranceCompany::query()
                ->where('status', 'approved')
                ->findOrFail($data['insurance_company_id']);

            $testRequest->update([
                'insurance_company_id' => $company->id,
            ]);

            return $this->syncInvoiceCoverage(
                $testRequest->refresh(),
                $invoice
            );
        });
    }

    public function calculateCoverage(TestRequest $testRequest): array
    {
        $invoice = $testRequest->invoice;

        if (!$invoice) {
            throw ValidationException::withMessages([
                'invoice' => ['Invoice not found for this test request.'],
            ]);
        }

        return $this->coverageResponse(
            $invoice->load([
                'items.testRequestItem.test',
                'testRequest.insuranceCompany',
                'testRequest.patient',
            ])
        );
    }

    public function syncInvoiceCoverage(
        TestRequest $testRequest,
        Invoice $invoice
    ): array {
        $testRequest->loadMissing([
            'items.test',
            'insuranceCompany',
            'patient',
        ]);

        $company = $testRequest->insuranceCompany;

        if ($company && $company->status !== 'approved') {
            throw ValidationException::withMessages([
                'insurance_company_id' => ['The selected insurance company is inactive.'],
            ]);
        }

        $rules = $company
            ? CoverageRule::query()
                ->where('insurance_company_id', $company->id)
                ->whereNotNull('test_id')
                ->get()
                ->keyBy('test_id')
            : collect();

        $invoice->items()->delete();

        $grossTotal = 0.0;
        $insuranceTotal = 0.0;

        foreach ($testRequest->items as $requestItem) {
            $lineTotal = round(
                (float) $requestItem->price * (int) $requestItem->quantity,
                2
            );

            $rule = $rules->get($requestItem->test_id);
            $coveragePercent = $company
                ? (float) ($rule?->coverage_percent ?? $company->default_coverage)
                : 0.0;
            $covered = round($lineTotal * $coveragePercent / 100, 2);

            if ($rule?->max_amount !== null) {
                $covered = min($covered, (float) $rule->max_amount);
            }

            $covered = min($lineTotal, max(0, $covered));
            $patientAmount = round($lineTotal - $covered, 2);

            $invoice->items()->create([
                'test_request_item_id' => $requestItem->id,
                'price' => $requestItem->price,
                'quantity' => $requestItem->quantity,
                'line_total' => $lineTotal,
                'coverage_percent' => $coveragePercent,
                'insurance_amount' => $covered,
                'patient_amount' => $patientAmount,
            ]);

            $grossTotal += $lineTotal;
            $insuranceTotal += $covered;
        }

        $grossTotal = round($grossTotal, 2);
        $insuranceTotal = round(min($grossTotal, $insuranceTotal), 2);
        $patientDue = round($grossTotal - $insuranceTotal, 2);

        $invoice->update([
            'total' => $grossTotal,
            'insurance_amount' => $insuranceTotal,
            'patient_due' => $patientDue,
            'paid' => 0,
            'remaining' => $patientDue,
            'status' => $patientDue <= 0 ? 'paid' : 'pending',
        ]);

        return $this->coverageResponse(
            $invoice->refresh()->load([
                'items.testRequestItem.test',
                'testRequest.insuranceCompany',
                'testRequest.patient',
            ])
        );
    }

    public function coverageResponse(Invoice $invoice): array
    {
        $testRequest = $invoice->testRequest;

        return [
            'test_request_id' => $testRequest->id,
            'request_number' => $testRequest->request_number,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'patient' => [
                'id' => $testRequest->patient->id,
                'name' => $testRequest->patient->name,
            ],
            'insurance_company' => $testRequest->insuranceCompany
                ? [
                    'id' => $testRequest->insuranceCompany->id,
                    'name' => $testRequest->insuranceCompany->name,
                ]
                : null,
            'gross_total' => $invoice->total,
            'insurance_amount' => $invoice->insurance_amount,
            'patient_due' => $invoice->patient_due,
            'paid' => $invoice->paid,
            'remaining' => $invoice->remaining,
            'payment_status' => $invoice->status,
            'items' => $invoice->items->map(fn($item) => [
                'id' => $item->id,
                'test_request_item_id' => $item->test_request_item_id,
                'test_id' => $item->testRequestItem?->test_id,
                'test_name' => $item->testRequestItem?->test?->name,
                'unit_price' => $item->price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'coverage_percent' => $item->coverage_percent,
                'insurance_amount' => $item->insurance_amount,
                'patient_amount' => $item->patient_amount,
            ])->values(),
        ];
    }
}
