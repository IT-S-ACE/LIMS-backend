<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_request_id' => ['required', 'uuid', 'exists:test_requests,id'],
            'method' => ['required', 'in:cash,card'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'test_request_id.required' => 'Test request is required.',
            'test_request_id.exists' => 'The selected test request does not exist.',
            'method.in' => 'Payment method must be cash or card.',
        ];
    }
}
