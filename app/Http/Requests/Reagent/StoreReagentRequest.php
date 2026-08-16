<?php

namespace App\Http\Requests\Reagent;

use Illuminate\Foundation\Http\FormRequest;

class StoreReagentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [

            'code' => [
                'required',
                'string',
                'unique:reagents,code'
            ],


            'name' => [
                'required',
                'string',
                'max:255'
            ],


            'category' => [
                'nullable',
                'string'
            ],



            'initial_quantity' => [
                'required',
                'numeric',
                'min:0'
            ],


            'min_stock' => [
                'required',
                'numeric',
                'min:0'
            ],


            'expiry_date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],

            'received_at' => ['required', 'date', 'before_or_equal:today'],

            'lot_number' => ['required', 'string', 'max:100', 'unique:reagent_lots,lot_number'],


            'unit_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'tests' => [
                'nullable',
                'array'
            ],

            'tests.*.test_id' => ['required', 'uuid', 'exists:tests,id', 'distinct'],

            'tests.*.quantity_used' => ['required', 'numeric', 'gt:0'],

        ];


    }
}
