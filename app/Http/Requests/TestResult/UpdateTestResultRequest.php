<?php

namespace App\Http\Requests\TestResult;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'value' => [
                'sometimes',
                'string',
            ],

            'status' => [
                'sometimes',
                'in:draft,completed',
            ],

            'flag' => [

                'sometimes',

                'in:normal,low,high,critical',

            ],
        ];
    }
}