<?php

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class CoverageRuleResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [

            'id'
            =>
                $this->id,


            'company'
            =>
                [
                    'id'
                    =>
                        $this->insuranceCompany?->id,


                    'name'
                    =>
                        $this->insuranceCompany?->name
                ],


            'test' => $this->test
                ? [
                    'id' => $this->test->id,
                    'name' => $this->test->name,
                ]
                : null,


            'coverage_percent'
            =>
                $this->coverage_percent,



            'max_amount'
            =>
                $this->max_amount,



            'created_at'
            =>
                $this->created_at

        ];

    }

}
