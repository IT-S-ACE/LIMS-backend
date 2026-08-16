<?php

namespace App\Http\Requests\Reagent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [

            'type' => [
                'required',
                Rule::in([
                    'add',
                    'consume'
                ])
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'reason' => ['required', 'string', 'min:3', 'max:500'],

            'reference' => ['nullable', 'string', 'max:255'],

            'lot_number' => [
                Rule::requiredIf($this->input('type') === 'add'),
                'nullable',
                'string',
                'max:100',
                'unique:reagent_lots,lot_number',
            ],

            'expiry_date' => [
                Rule::requiredIf($this->input('type') === 'add'),
                'nullable',
                'date',
                'after_or_equal:today',
            ],

            'received_at' => [
                Rule::requiredIf($this->input('type') === 'add'),
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ];


    }
}
