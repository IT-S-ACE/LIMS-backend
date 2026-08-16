<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [

            'id'=>$this->id,

            'username'=>$this->username,

            'email'=>$this->email,

            'role'=>$this->role,

            'status'=>$this->status,

            'created_at'=>$this->created_at

        ];
    }

}