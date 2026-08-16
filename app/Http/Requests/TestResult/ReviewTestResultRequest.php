<?php

namespace App\Http\Requests\TestResult;

use Illuminate\Foundation\Http\FormRequest;

class ReviewTestResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
