<?php

namespace App\Http\Requests\TestRequest;

use Illuminate\Foundation\Http\FormRequest;

class SearchTestRequestRequest extends FormRequest
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
                'max:255',
            ],

            'patient_id' => [
                'nullable',
                'uuid',
                'exists:patients,id',
            ],

            'status' => [
                'nullable',
                'in:pending,processing,completed,cancelled',
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