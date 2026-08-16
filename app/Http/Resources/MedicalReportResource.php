<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $testRequest = $this->testRequest;
        $results = $testRequest?->samples
            ?->flatMap(fn($sample) => $sample->testResults)
            ->sortBy(fn($result) => $result->testRequestItem?->test?->name)
            ->values() ?? collect();

        return [
            'id' => $this->id,
            'test_request_id' => $this->test_request_id,
            'generated_at' => $this->generated_at?->toDateTimeString(),
            'pdf_download_url' => url("/api/user/medical-reports/{$this->id}/pdf"),
            'request' => $testRequest
                ? [
                    'id' => $testRequest->id,
                    'request_number' => $testRequest->request_number,
                    'status' => $testRequest->status,
                    'created_at' => $testRequest->created_at?->toDateTimeString(),
                ]
                : null,
            'billing' => $testRequest?->invoice
                ? [
                    'invoice_number' => $testRequest->invoice->invoice_number,
                    'gross_total' => $testRequest->invoice->total,
                    'insurance_amount' => $testRequest->invoice->insurance_amount,
                    'patient_due' => $testRequest->invoice->patient_due,
                    'paid' => $testRequest->invoice->paid,
                    'remaining' => $testRequest->invoice->remaining,
                    'payment_status' => $testRequest->invoice->status,
                ]
                : null,
            'patient' => $testRequest?->patient
                ? [
                    'id' => $testRequest->patient->id,
                    'patient_number' => $testRequest->patient->patient_number,
                    'name' => $testRequest->patient->name,
                    'gender' => $testRequest->patient->gender,
                    'dob' => $testRequest->patient->dob?->format('Y-m-d'),
                    'phone' => $testRequest->patient->phone,
                    'email' => $testRequest->patient->email,
                ]
                : null,
            'samples' => $testRequest?->samples?->map(fn($sample) => [
                'id' => $sample->id,
                'sample_number' => $sample->sample_number,
                'barcode' => $sample->barcode,
                'sample_type' => $sample->sample_type,
                'collected_at' => $sample->collected_at?->toDateTimeString(),
            ])->values() ?? [],
            'results' => $results->map(fn($result) => [
                'id' => $result->id,
                'result_number' => $result->result_number,
                'test_name' => $result->testRequestItem?->test?->name,
                'value' => $result->value,
                'unit' => $result->value_unit,
                'reference_range' => $result->reference_range,
                'flag' => $result->flag,
                'notes' => $result->notes,
                'entered_by' => $result->enteredBy?->username,
                'reviewed_by' => $result->reviewedBy?->username,
                'approved_by' => $result->approvedBy?->username,
                'approved_at' => $result->approved_at?->toDateTimeString(),
            ])->values(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
