<?php

namespace App\Http\Requests\Insurance;


use Illuminate\Foundation\Http\FormRequest;



class ApplyInsuranceRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }




    public function rules(): array
    {

        return [

            'insurance_company_id'
            =>
                'required|exists:insurance_companies,id'

        ];

    }


}