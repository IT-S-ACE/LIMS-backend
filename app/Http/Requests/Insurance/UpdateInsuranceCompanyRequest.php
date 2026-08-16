<?php

namespace App\Http\Requests\Insurance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateInsuranceCompanyRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {

        return [

            'code'
            =>
                [
                    'sometimes',
                    'string',
                    'max:50',
                    Rule::unique('insurance_companies', 'code')
                        ->ignore($this->route('insuranceCompany')->id),
                ],


            'name'
            =>
                'sometimes|string|max:255',


            'email'
            =>
                'nullable|email',


            'phone'
            =>
                'nullable|string|max:20',


            'default_coverage'
            =>
                'sometimes|numeric|min:0|max:100',


            'status'
            =>
                'sometimes|in:approved,inactive'

        ];

    }

}
