<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        return [

            'id' => $this->id,


            'title' => match ($this->type) {

                'low_stock' => 'Low Stock Alert',

                'result_ready' => 'Result Ready',

                default => 'Notification'

            },


            'type' => $this->type,


            'message' => $this->message,


            'channel' => $this->channel,


            'status' => $this->status,


            'read_at' => $this->read_at,


            'created_at' => $this->created_at,

        ];
    }
}