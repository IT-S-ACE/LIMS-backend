<?php

namespace App\Http\Requests\TestRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'uuid',
                'exists:patients,id',
            ],

            'insurance_company_id' => [
                'nullable',
                'uuid',
                'exists:insurance_companies,id',
            ],

            'tests' => [
                'required',
                'array',
                'min:1',
            ],

            'tests.*.test_id' => [
                'required',
                'uuid',
                'distinct',
                'exists:tests,id',
            ],

            'tests.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' =>
                'Patient is required.',

            'patient_id.exists' =>
                'Patient does not exist.',

            'insurance_company_id.exists' =>
                'Insurance company does not exist.',

            'tests.required' =>
                'At least one test is required.',

            'tests.array' =>
                'Tests must be an array.',

            'tests.min' =>
                'At least one test is required.',

            'tests.*.test_id.required' =>
                'Test ID is required.',

            'tests.*.test_id.distinct' =>
                'Duplicate tests are not allowed.',

            'tests.*.test_id.exists' =>
                'Selected test does not exist.',

            'tests.*.quantity.required' =>
                'Test quantity is required.',

            'tests.*.quantity.min' =>
                'Test quantity must be at least 1.',
        ];
    }
}