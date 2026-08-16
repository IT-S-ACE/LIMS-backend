<?php

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class InsuranceCompanyResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [

            'id' => $this->id,


            'code' => $this->code,


            'name' => $this->name,


            'contact' => [

                'email' => $this->email,

                'phone' => $this->phone

            ],


            'default_coverage' =>
                $this->default_coverage,


            'status' =>
                $this->status,


            'created_at' =>
                $this->created_at


        ];

    }

}