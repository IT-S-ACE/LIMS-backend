<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReagentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $availableStock = array_key_exists('available_stock_qty', $this->resource->getAttributes())
            ? (float) ($this->available_stock_qty ?? 0)
            : (float) $this->stock_qty;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'stock_qty' => $availableStock,
            'min_stock' => (float) $this->min_stock,
            'is_low_stock' => $availableStock <= (float) $this->min_stock,
            'nearest_expiry_date' => $this->nearestExpiryDate(),
            'unit_price' => (float) $this->unit_price,
            'tests' => $this->whenLoaded('tests', fn() => $this->tests->map(fn($test) => [
                'id' => $test->id,
                'name' => $test->name,
                'quantity_used' => (float) $test->pivot->quantity_used,
            ])),
            'lots' => $this->whenLoaded('lots', fn() => $this->lots->map(fn($lot) => [
                'id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'initial_quantity' => (float) $lot->initial_quantity,
                'remaining_quantity' => (float) $lot->remaining_quantity,
                'expiry_date' => $lot->expiry_date?->format('Y-m-d'),
                'received_at' => $lot->received_at?->format('Y-m-d'),
                'unit_price' => (float) $lot->unit_price,
                'status' => $lot->expiry_date?->isBefore(today())
                    ? 'expired'
                    : ((float) $lot->remaining_quantity <= 0 ? 'depleted' : 'available'),
            ])),
            'movements' => $this->whenLoaded(
                'stockTransactions',
                fn() => $this->stockTransactions->map(fn($movement) => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'quantity' => (float) $movement->quantity,
                    'reason' => $movement->reason,
                    'reference' => $movement->reference,
                    'lot_number' => $movement->lot?->lot_number,
                    'sample_id' => $movement->sample_id,
                    'date' => $movement->date?->toDateTimeString(),
                ])
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function nearestExpiryDate(): ?string
    {
        if (!$this->relationLoaded('lots')) {
            return $this->expiry_date?->format('Y-m-d');
        }

        return $this->lots
            ->filter(fn($lot) =>
                (float) $lot->remaining_quantity > 0
                && !$lot->expiry_date->isBefore(today()))
            ->sortBy('expiry_date')
            ->first()?->expiry_date?->format('Y-m-d');
    }
}
