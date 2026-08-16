<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $testRequest = $this->sample?->testRequest;

        return [
            'id' => $this->id,
            'result_number' => $this->result_number,
            'sample_id' => $this->sample_id,
            'sample_number' => $this->sample?->sample_number,
            'barcode' => $this->sample?->barcode,
            'sample_status' => $this->sample?->status,
            'test_request_id' => $testRequest?->id,
            'request_number' => $testRequest?->request_number,
            'request_status' => $testRequest?->status,
            'medical_report_id' => $testRequest?->medicalReport?->id,
            'patient' => $testRequest?->patient
                ? [
                    'id' => $testRequest->patient->id,
                    'name' => $testRequest->patient->name,
                    'patient_number' => $testRequest->patient->patient_number,
                ]
                : null,
            'test_request_item_id' => $this->test_request_item_id,
            'test' => $this->testRequestItem?->test
                ? [
                    'id' => $this->testRequestItem->test->id,
                    'name' => $this->testRequestItem->test->name,
                    'result_type' => $this->testRequestItem->test->result_type ?? 'text',
                    'result_options' => $this->testRequestItem->test->result_options ?? [],
                ]
                : null,
            'value' => $this->value,
            'value_unit' => $this->value_unit,
            'reference_range' => $this->reference_range,
            'flag' => $this->flag,
            'status' => $this->workflow_status ?? ($this->approved ? 'approved' : 'draft'),
            'notes' => $this->notes,
            'correction_reason' => $this->correction_reason,
            'review_notes' => $this->review_notes,
            'approved' => $this->approved,
            'entered_by' => $this->userSummary($this->enteredBy),
            'entered_at' => $this->entered_at?->toDateTimeString(),
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'reviewed_by' => $this->userSummary($this->reviewedBy),
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'approved_by' => $this->userSummary($this->approvedBy),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'timeline' => $this->whenLoaded(
                'statusHistories',
                fn() => $this->statusHistories->map(fn($history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'reason' => $history->reason,
                    'changed_by' => $this->userSummary($history->changedBy),
                    'created_at' => $history->created_at?->toDateTimeString(),
                ])
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function userSummary($user): ?array
    {
        return $user
            ? [
                'id' => $user->id,
                'name' => $user->username,
                'role' => $user->role,
            ]
            : null;
    }
}
