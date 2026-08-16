<?php

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class SystemSettingResource extends JsonResource
{


    public function toArray(
        Request $request
    ): array {


        return [

            'id'
            =>
                $this->id,


            'laboratory' => [


                'name'
                =>
                    $this->lab_name,


                'license_number'
                =>
                    $this->license_number,


                'address'
                =>
                    $this->address

            ],



            'notifications' => [


                'email'
                =>
                    $this->email_notifications


            ],



            'updated_at'
            =>
                $this->updated_at


        ];

    }

}