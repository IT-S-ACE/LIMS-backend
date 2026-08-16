<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'test_id' => $this->test_id,

            'test' => $this->whenLoaded(
                'test',
                function () {
                    return [
                        'id' => $this->test->id,
                        'name' => $this->test->name,
                        'price' => $this->test->price,
                        'reference_range' =>
                            $this->test->reference_range,
                    ];
                }
            ),

            'quantity' => $this->quantity,

            'price' => $this->price,

            'subtotal' => number_format(
                (float) $this->price * $this->quantity,
                2,
                '.',
                ''
            ),

            'created_at' =>
                $this->created_at?->toDateTimeString(),

            'updated_at' =>
                $this->updated_at?->toDateTimeString(),
        ];
    }
}