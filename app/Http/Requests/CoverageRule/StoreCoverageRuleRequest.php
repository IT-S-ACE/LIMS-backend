<?php

namespace App\Http\Requests\CoverageRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreCoverageRuleRequest extends FormRequest
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
                'required|exists:insurance_companies,id',


            'test_id' => [
                'required',
                'uuid',
                'exists:tests,id',
                Rule::unique('coverage_rules', 'test_id')
                    ->where(fn($query) => $query->where(
                        'insurance_company_id',
                        $this->input('insurance_company_id')
                    )),
            ],

            'coverage_percent'
            =>
                [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100'
                ],



            'max_amount'
            =>
                [
                    'nullable',
                    'numeric',
                    'min:0'
                ]

        ];

    }

}
