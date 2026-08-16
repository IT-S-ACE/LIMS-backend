<?php

namespace App\Http\Requests\TestResult;

use Illuminate\Foundation\Http\FormRequest;


class StoreTestResultRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }



    public function rules(): array
    {

        return [

            'sample_id' => [
                'required',
                'uuid',
                'exists:samples,id',
            ],

            'test_request_item_id' => [
                'required',
                'uuid',
                'exists:test_request_items,id',
            ],

            'value' => [
                'required',
                'string',
            ],

            'status' => [

                'nullable',

                'in:draft,completed',

            ],
            
            'flag' => [

                'nullable',

                'in:normal,low,high,critical',

            ],
        ];

    }

}
