<?php

namespace App\Http\Requests\Sample;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class RegisterSampleRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }



    public function rules(): array
    {

        return [

            'test_request_id' =>
                [
                    'required',
                    'uuid',
                    'exists:test_requests,id'
                ],

            'sample_type' => [

                'required',

                Rule::in([
                    'blood',
                    'serum',
                    'plasma',
                    'urine',
                    'stool',
                    'swab',
                    'tissue',
                    'other',
                ]),

            ],
        ];

    }

}
