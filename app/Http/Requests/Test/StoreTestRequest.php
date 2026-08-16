<?php

namespace App\Http\Requests\Test;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:tests,name',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'reference_range' => [
                'required',
                'string',
                'max:255',
            ],
            'unit' => 'required|string|max:50',
            'result_type' => ['required', 'in:numeric,text,choice'],
            'result_options' => ['nullable', 'array', 'min:2', 'required_if:result_type,choice'],
            'result_options.*' => ['required', 'string', 'max:100', 'distinct'],
            'critical_low' => ['nullable', 'numeric'],
            'critical_high' => ['nullable', 'numeric', 'gt:critical_low'],
        ];
    }
}
