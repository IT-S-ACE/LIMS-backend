<?php

namespace App\Http\Requests\Sample;

use Illuminate\Foundation\Http\FormRequest;


class SearchSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'search' => [

                'nullable',

                'string',

            ],

            'status' => [

                'nullable',

                'in:registered,collected,in_progress,completed,rejected,cancelled',

            ],

            'test_request_id' => [
                'nullable',
                'uuid',
                'exists:test_requests,id',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [

                'nullable',

                'integer',

                'min:1',

                'max:100',

            ],

        ];
    }
}
