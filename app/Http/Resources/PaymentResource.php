<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'test_request_id' => $this->invoice?->test_request_id,
            'request_number' => $this->invoice?->testRequest?->request_number,
            'patient' => $this->invoice?->testRequest?->patient
                ? [
                    'id' => $this->invoice->testRequest->patient->id,
                    'patient_number' => $this->invoice->testRequest->patient->patient_number,
                    'name' => $this->invoice->testRequest->patient->name,
                    'phone' => $this->invoice->testRequest->patient->phone,
                ]
                : null,
            'insurance_company' => $this->invoice?->testRequest?->insuranceCompany
                ? [
                    'id' => $this->invoice->testRequest->insuranceCompany->id,
                    'name' => $this->invoice->testRequest->insuranceCompany->name,
                ]
                : null,
            'amount' => $this->amount,
            'method' => $this->method,
            'notes' => $this->notes,
            'recorded_by' => $this->recordedBy
                ? [
                    'id' => $this->recordedBy->id,
                    'name' => $this->recordedBy->username,
                ]
                : null,
            'date' => $this->date?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
