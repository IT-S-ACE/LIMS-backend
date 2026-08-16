<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $testRequest = $this->testRequest;

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'test_request_id' => $this->test_request_id,
            'request_number' => $testRequest?->request_number,
            'patient' => $testRequest?->patient
                ? [
                    'id' => $testRequest->patient->id,
                    'patient_number' => $testRequest->patient->patient_number,
                    'name' => $testRequest->patient->name,
                    'phone' => $testRequest->patient->phone,
                ]
                : null,
            'insurance_company' => $testRequest?->insuranceCompany
                ? [
                    'id' => $testRequest->insuranceCompany->id,
                    'name' => $testRequest->insuranceCompany->name,
                ]
                : null,
            'gross_total' => $this->total,
            'insurance_amount' => $this->insurance_amount,
            'patient_due' => $this->patient_due,
            'paid' => $this->paid,
            'remaining' => $this->remaining,
            'status' => $this->status,
            'items' => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id' => $item->id,
                    'test_name' => $item->testRequestItem?->test?->name,
                    'unit_price' => $item->price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                    'coverage_percent' => $item->coverage_percent,
                    'insurance_amount' => $item->insurance_amount,
                    'patient_amount' => $item->patient_amount,
                ])->values()
            ),
            'payments' => $this->whenLoaded('payments', fn() =>
                PaymentResource::collection($this->payments)
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
