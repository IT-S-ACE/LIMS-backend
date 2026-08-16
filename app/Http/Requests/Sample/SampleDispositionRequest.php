<?php

namespace App\Http\Requests\Sample;

use Illuminate\Foundation\Http\FormRequest;

class SampleDispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ];
    }
}
