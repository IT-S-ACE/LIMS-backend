<?php

namespace App\Http\Requests\FinancialReport;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FinancialReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->query('from', now()->subDays(29)->toDateString()),
            'to' => $this->query('to', now()->toDateString()),
        ]);
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $days = Carbon::createFromFormat('Y-m-d', $this->input('from'))
                    ->diffInDays(Carbon::createFromFormat('Y-m-d', $this->input('to')));

                if ($days > 730) {
                    $validator->errors()->add(
                        'to',
                        'The financial report period cannot exceed 731 days.'
                    );
                }
            }
        );
    }
}
