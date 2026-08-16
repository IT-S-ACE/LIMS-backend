<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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
            ],

            'gender' => [
                'required',
                'in:male,female'
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
            ],
            
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'dob' => [
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

            'dob.required' => 'Date of birth is required.',

            'dob.date' => 'Date of birth must be a valid date.',

            'dob.before_or_equal' => 'Date of birth cannot be in the future.',
        ];
    }
}