<?php

namespace App\Http\Requests\TestResult;

use Illuminate\Foundation\Http\FormRequest;

class SaveSampleResultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'lab_technician'], true);
    }

    public function rules(): array
    {
        return [
            'results' => ['required', 'array', 'min:1'],
            'results.*.test_request_item_id' => [
                'required',
                'uuid',
                'distinct',
                'exists:test_request_items,id',
            ],
            'results.*.value' => ['required', 'string', 'max:5000'],
            'results.*.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
