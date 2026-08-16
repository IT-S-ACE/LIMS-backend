<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $results = $this->relationLoaded('results')
            ? $this->results->keyBy('test_request_item_id')
            : collect();

        return [
            'sample_id' => $this->id,
            'sample_number' => $this->sample_number,
            'barcode' => $this->barcode,
            'sample_status' => $this->status,
            'patient' => $this->testRequest?->patient
                ? [
                    'id' => $this->testRequest->patient->id,
                    'name' => $this->testRequest->patient->name,
                ]
                : null,
            'request' => $this->testRequest
                ? [
                    'id' => $this->testRequest->id,
                    'request_number' => $this->testRequest->request_number,
                    'status' => $this->testRequest->status,
                ]
                : null,
            'tests' => $this->testRequest?->items
                ?->filter(fn($item) => $item->test)
                ->map(function ($item) use ($results) {
                    $result = $results->get($item->id);

                    return [
                        'test_request_item_id' => $item->id,
                        'quantity' => $item->quantity,
                        'test' => [
                            'id' => $item->test->id,
                            'name' => $item->test->name,
                            'unit' => $item->test->unit,
                            'reference_range' => $item->test->reference_range,
                            'result_type' => $item->test->result_type ?? 'text',
                            'result_options' => $item->test->result_options ?? [],
                            'critical_low' => $item->test->critical_low,
                            'critical_high' => $item->test->critical_high,
                        ],
                        'result' => $result ? new TestResultResource($result) : null,
                    ];
                })
                ->values() ?? [],
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
