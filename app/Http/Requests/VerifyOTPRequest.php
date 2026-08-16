<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOTPRequest extends FormRequest
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

            'otp' => [
                'required',
                'digits:6',
            ],

            'type' => [
                'required',
                'in:register,login,reset_password',
            ],
        ];
    }
}
