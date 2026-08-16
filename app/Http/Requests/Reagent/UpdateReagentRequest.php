<?php

namespace App\Http\Requests\Reagent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReagentRequest extends FormRequest
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

                'string',

                'max:255',

                Rule::unique('reagents', 'name')
                    ->ignore($this->route('reagent'))

            ],

            'code' => ['sometimes', 'string', Rule::unique('reagents', 'code')->ignore($this->route('reagent'))],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'min_stock' => ['sometimes', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'tests' => ['sometimes', 'array'],
            'tests.*.test_id' => ['required', 'uuid', 'exists:tests,id', 'distinct'],
            'tests.*.quantity_used' => ['required', 'numeric', 'gt:0'],

        ];
    }
}
