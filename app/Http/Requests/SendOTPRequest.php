<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOTPRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],

            'type' => [
                'required',
                'in:register,login,reset_password',
            ],

        ];
    }
}
