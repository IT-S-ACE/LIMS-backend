<?php

namespace App\Http\Requests\Test;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',

                Rule::unique('tests', 'name')
                    ->ignore($this->route('test')),
            ],

            'price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'reference_range' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'unit' => 'nullable|string|max:50',
            'result_type' => ['sometimes', 'required', 'in:numeric,text,choice'],
            'result_options' => ['nullable', 'array', 'min:2', 'required_if:result_type,choice'],
            'result_options.*' => ['required', 'string', 'max:100', 'distinct'],
            'critical_low' => ['nullable', 'numeric'],
            'critical_high' => ['nullable', 'numeric', 'gt:critical_low'],
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }
}
