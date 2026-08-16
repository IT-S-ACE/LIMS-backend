<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->testRequest?->items ?? collect();
        $nextStatus = match ($this->status) {
            'registered' => 'collected',
            'collected' => 'in_progress',
            'in_progress' => 'completed',
            default => null,
        };

        return [
            'id' => $this->id,
            'sample_number' => $this->sample_number,
            'barcode' => $this->barcode,
            'qr_code' => $this->qr_code,
            'sample_type' => $this->sample_type,
            'status' => $this->status,
            'next_status' => $nextStatus,
            'collected_at' => $this->collected_at?->toDateTimeString(),
            'rejected_reason' => $this->rejected_reason,
            'cancelled_reason' => $this->cancelled_reason,
            'reagents_consumed_at' => $this->reagents_consumed_at?->toDateTimeString(),
            'results_count' => $this->relationLoaded('results')
                ? $this->results->count()
                : null,
            'tests' => $items
                ->filter(fn($item) => $item->test)
                ->map(fn($item) => [
                    'id' => $item->test->id,
                    'name' => $item->test->name,
                    'quantity' => $item->quantity,
                ])
                ->values(),
            'patient' => $this->testRequest?->patient
                ? [
                    'id' => $this->testRequest->patient->id,
                    'name' => $this->testRequest->patient->name,
                    'patient_number' => $this->testRequest->patient->patient_number,
                ]
                : null,
            'request' => $this->testRequest
                ? [
                    'id' => $this->testRequest->id,
                    'request_number' => $this->testRequest->request_number,
                    'status' => $this->testRequest->status,
                ]
                : null,
            'timeline' => $this->whenLoaded(
                'statusHistories',
                fn() => $this->statusHistories->map(fn($history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'reason' => $history->reason,
                    'changed_by' => $history->changedBy
                        ? [
                            'id' => $history->changedBy->id,
                            'name' => $history->changedBy->username,
                        ]
                        : null,
                    'created_at' => $history->created_at?->toDateTimeString(),
                ])
            ),
            'reagent_consumptions' => $this->whenLoaded(
                'reagentConsumptions',
                fn() => $this->reagentConsumptions->map(fn($consumption) => [
                    'id' => $consumption->id,
                    'reagent_id' => $consumption->reagent_id,
                    'reagent_name' => $consumption->reagent?->name,
                    'reagent_code' => $consumption->reagent?->code,
                    'lot_number' => $consumption->lot?->lot_number,
                    'test_name' => $consumption->testRequestItem?->test?->name,
                    'quantity' => (float) $consumption->quantity,
                    'created_at' => $consumption->created_at?->toDateTimeString(),
                ])
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
