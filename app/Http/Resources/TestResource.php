<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'price' => $this->price,

            'unit' => $this->unit,

            'reference_range' => $this->reference_range,

            'result_type' => $this->result_type ?? 'text',

            'result_options' => $this->result_options ?? [],

            'critical_low' => $this->critical_low,

            'critical_high' => $this->critical_high,

            'reagents_count' => $this->whenCounted(
                'reagents'
            ),

            'reagents' => $this->whenLoaded(
                'reagents',
                function () {
                    return $this->reagents->map(
                        function ($reagent) {
                            return [
                                'id' => $reagent->id,

                                'name' => $reagent->name,

                                'quantity_used' =>
                                    $reagent->pivot->quantity_used,
                            ];
                        }
                    );
                }
            ),

            'created_at' => $this->created_at
                    ?->format('Y-m-d H:i:s'),

            'updated_at' => $this->updated_at
                    ?->format('Y-m-d H:i:s'),
        ];
    }
}
