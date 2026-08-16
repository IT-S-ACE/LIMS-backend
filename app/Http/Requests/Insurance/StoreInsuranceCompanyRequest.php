<?php

namespace App\Http\Requests\Insurance;

use Illuminate\Foundation\Http\FormRequest;


class StoreInsuranceCompanyRequest extends FormRequest
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
                'required|string|max:50|unique:insurance_companies,code',


            'name'
            =>
                'required|string|max:255',


            'email'
            =>
                'nullable|email',


            'phone'
            =>
                'nullable|string|max:20',



            'default_coverage'
            =>
                'required|numeric|min:0|max:100',


            'status'
            =>
                'nullable|in:approved,inactive'

        ];

    }

}