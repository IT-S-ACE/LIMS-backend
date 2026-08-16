<?php

namespace App\Http\Requests\CoverageRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateCoverageRuleRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }



    public function rules(): array
    {

    return [

        'test_id' => [
            'sometimes',
            'required',
            'uuid',
            'exists:tests,id',
            Rule::unique('coverage_rules', 'test_id')
                ->where(fn($query) => $query->where(
                    'insurance_company_id',
                    $this->route('coverageRule')->insurance_company_id
                ))
                ->ignore($this->route('coverageRule')->id),
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
