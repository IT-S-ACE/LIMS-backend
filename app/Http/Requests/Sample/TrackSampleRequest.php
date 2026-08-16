<?php

namespace App\Http\Requests\Sample;


use Illuminate\Foundation\Http\FormRequest;


class TrackSampleRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {

        return [

            'qrCode' =>
                [
                    'required',
                    'string'
                ]

        ];

    }

}