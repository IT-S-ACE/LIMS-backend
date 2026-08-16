<?php

namespace App\Http\Requests\Sample;


use Illuminate\Foundation\Http\FormRequest;


class UpdateSampleStatusRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }



    public function rules(): array
    {

        return [

            'status' => [
                'required',
                'string',
                'in:collected,in_progress,completed',
            ],

        ];

    }

}
