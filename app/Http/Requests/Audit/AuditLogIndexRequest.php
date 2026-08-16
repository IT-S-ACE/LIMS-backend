<?php

namespace App\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'user_id' => ['nullable', 'uuid'],
            'actor_role' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'string', 'max:80'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'entity_id' => ['nullable', 'uuid'],
            'result' => ['nullable', 'in:SUCCESS,DENIED'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->filled('from') && $value < $this->input('from')) {
                        $fail('The to date must be after or equal to the from date.');
                    }
                },
            ],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
