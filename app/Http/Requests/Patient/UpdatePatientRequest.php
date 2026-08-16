<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
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
            ],

            'gender' => [
                'sometimes',
                'required',
                'in:male,female'
            ],

            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:20',
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],

            'dob' => [
                'sometimes',
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Patient name is required.',

            'phone.required' => 'Patient phone is required.',

            'email.email' => 'Email address is invalid.',

            'dob.date' => 'Date of birth must be a valid date.',

            'dob.before_or_equal' => 'Date of birth cannot be in the future.',
        ];
    }
}