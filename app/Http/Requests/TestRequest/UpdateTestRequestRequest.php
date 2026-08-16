<?php

namespace App\Http\Requests\TestRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:patients,id',
            ],

            'insurance_company_id' => [
                'sometimes',
                'nullable',
                'uuid',
                'exists:insurance_companies,id',
            ],

            'status' => [
                'sometimes',
                'required',
                'in:pending,processing,completed,cancelled',
            ],

            'tests' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],

            'tests.*.test_id' => [
                'required_with:tests',
                'uuid',
                'distinct',
                'exists:tests,id',
            ],

            'tests.*.quantity' => [
                'required_with:tests',
                'integer',
                'min:1',
                'max:100',
            ],

            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.exists' =>
                'Patient does not exist.',

            'insurance_company_id.exists' =>
                'Insurance company does not exist.',

            'status.in' =>
                'Invalid test request status.',

            'tests.min' =>
                'At least one test is required.',

            'tests.*.test_id.distinct' =>
                'Duplicate tests are not allowed.',

            'tests.*.test_id.exists' =>
                'Selected test does not exist.',

            'tests.*.quantity.min' =>
                'Test quantity must be at least 1.',

            'reason.required' =>
                'A reason for modifying the test request is required.',
        ];
    }
}
