<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'request_number' =>

                $this->request_number,

            'status' => $this->status,

            'total_price' => $this->total_price,

            'insurance_amount' => $this->invoice?->insurance_amount ?? 0,

            'patient_due' => $this->invoice?->patient_due ?? $this->total_price,

            'paid' => $this->invoice?->paid ?? 0,

            'remaining' => $this->invoice?->remaining ?? $this->total_price,

            'payment_status' => $this->invoice?->status ?? 'pending',

            'invoice' => $this->invoice
                ? [
                    'id' => $this->invoice->id,
                    'invoice_number' => $this->invoice->invoice_number,
                ]
                : null,

            'patient' => $this->whenLoaded(
                'patient',
                function () {
                    return [
                        'id' => $this->patient->id,
                        'name' => $this->patient->name,
                        'phone' => $this->patient->phone,
                        'email' => $this->patient->email,
                        'dob' => $this->patient
                            ->dob?->format('Y-m-d'),
                    ];
                }
            ),

            'insurance_company' => $this->whenLoaded(
                'insuranceCompany',
                function () {
                    if (!$this->insuranceCompany) {
                        return null;
                    }

                    return [
                        'id' =>
                            $this->insuranceCompany->id,

                        'name' =>
                            $this->insuranceCompany->name,
                    ];
                }
            ),

            'items' => TestRequestItemResource::collection(
                $this->whenLoaded('items')
            ),

            'samples' => $this->whenLoaded(
                'samples',
                fn() => $this->samples->map(fn($sample) => [
                    'id' => $sample->id,
                    'sample_number' => $sample->sample_number,
                    'barcode' => $sample->barcode,
                    'sample_type' => $sample->sample_type,
                    'status' => $sample->status,
                ])
            ),

            'tests_summary' =>

                $this->items
                    ->pluck('test.name')
                    ->implode(', '),

            'created_at' =>
                $this->created_at?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at?->toDateTimeString(),
        ];
    }
}
