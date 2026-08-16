<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $testRequests = $this->whenLoaded('testRequests');

        $samples = $this->relationLoaded('testRequests')
            ? $this->testRequests->flatMap(
                fn($testRequest) => $testRequest->samples
            )
            : collect();

        $results = $samples->flatMap(
            fn($sample) => $sample->results
        );

        $reports = $this->relationLoaded('testRequests')
            ? $this->testRequests
            ->map(fn($testRequest) => $testRequest->medicalReport)
            ->filter()
            ->values()
            : collect();

        return [
            'id' => $this->id,

            'patient_number' => $this->patient_number,

            'user_id' => $this->user_id,

            'name' => $this->name,

            'gender' => $this->gender,

            'phone' => $this->phone,

            'email' => $this->email,

            'dob' => optional($this->dob)->format('Y-m-d'),

            'insurance' => $this->relationLoaded('testRequests')

                ? $this->testRequests

                    ->pluck('insuranceCompany')

                    ->filter()

                    ->unique('id')

                    ->values()

                    ->map(function ($company) {

                        return [

                            'id' => $company->id,

                            'name' => $company->name,

                        ];

                    })

                : [],



            'balance' => $this->relationLoaded('testRequests')

                ? $this->testRequests

                    ->pluck('invoice')

                    ->filter()

                    ->sum('remaining')

                : 0,

            'statistics' => [
                'test_requests_count' => $this->test_requests_count
                    ?? $this->testRequests->count(),

                'samples_count' => $samples->count(),

                'results_count' => $results->count(),

                'approved_results_count' => $results
                    ->where('approved', true)
                    ->count(),

                'pending_results_count' => $results
                    ->where('approved', false)
                    ->count(),

                'medical_reports_count' => $reports->count(),

                'notifications_count' => $this->notifications_count
                    ?? $this->notifications->count(),
            ],

            'test_requests' => $this->whenLoaded(
                'testRequests',
                function () {
                    return $this->testRequests->map(function ($testRequest) {

                        return [
                            'id' => $testRequest->id,

                            'status' => $testRequest->status,

                            'total_price' => $testRequest->total_price,

                            'insurance_company' =>
                                $testRequest->insuranceCompany
                                ? [
                                    'id' => $testRequest
                                        ->insuranceCompany
                                        ->id,

                                    'name' => $testRequest
                                        ->insuranceCompany
                                        ->name,
                                ]
                                : null,

                            'tests' => $testRequest->items->map(
                                function ($item) {
                                    return [
                                        'id' => $item->id,

                                        'quantity' => $item->quantity,

                                        'price' => $item->price,

                                        'test' => $item->test
                                            ? [
                                                'id' => $item->test->id,

                                                'name' => $item->test->name,

                                                'price' => $item->test->price,
                                            ]
                                            : null,
                                    ];
                                }
                            ),

                            'samples' => $testRequest->samples->map(
                                function ($sample) use ($testRequest) {

                                    return [
                                        'id' => $sample->id,

                                        'qr_code' => $sample->qr_code,

                                        'status' => $sample->status,

                                        'received_at' => optional(
                                            $sample->received_at
                                        )->format('Y-m-d H:i:s'),

                                        'results_count' =>
                                            $sample->results->count(),

                                        'results' => $sample->results->map(
                                            function ($result) use ($testRequest) {

                                                return [
                                                    'id' => $result->id,

                                                    'value' => $result->value,

                                                    'status' => $result->workflow_status
                                                        ?? ($result->approved ? 'approved' : 'draft'),

                                                    'approved' =>
                                                        $result->approved,

                                                    'medical_report' =>
                                                        $testRequest->medicalReport
                                                        ? [
                                                            'id' =>
                                                                $testRequest
                                                                    ->medicalReport
                                                                    ->id,

                                                            'pdf_path' =>
                                                                $testRequest
                                                                    ->medicalReport
                                                                    ->pdf_path,

                                                            'generated_at' =>
                                                                optional(
                                                                    $testRequest
                                                                        ->medicalReport
                                                                        ->generated_at
                                                                )->format(
                                                                        'Y-m-d H:i:s'
                                                                    ),
                                                        ]
                                                        : null,
                                                ];
                                            }
                                        ),
                                    ];
                                }
                            ),

                            'invoice' => $testRequest->invoice
                                ? [
                                    'id' => $testRequest->invoice->id,

                                    'total' =>
                                        $testRequest->invoice->total,

                                    'paid' =>
                                        $testRequest->invoice->paid,

                                    'remaining' =>
                                        $testRequest->invoice->remaining,

                                    'status' =>
                                        $testRequest->invoice->status,

                                    'payments_count' =>
                                        $testRequest
                                            ->invoice
                                            ->payments
                                            ->count(),

                                    'refunds_count' =>
                                        $testRequest
                                            ->invoice
                                            ->refunds
                                            ->count(),
                                ]
                                : null,

                            'created_at' => optional(
                                $testRequest->created_at
                            )->format('Y-m-d H:i:s'),

                            'updated_at' => optional(
                                $testRequest->updated_at
                            )->format('Y-m-d H:i:s'),
                        ];
                    });
                }
            ),

            'notifications' => $this->whenLoaded(
                'notifications',
                function () {
                    return $this->notifications->map(
                        function ($notification) {
                            return [
                                'id' => $notification->id,

                                'message' => $notification->message,

                                'status' => $notification->status,

                                'created_at' => optional(
                                    $notification->created_at
                                )->format('Y-m-d H:i:s'),
                            ];
                        }
                    );
                }
            ),

            'created_at' => optional(
                $this->created_at
            )->format('Y-m-d H:i:s'),

            'updated_at' => optional(
                $this->updated_at
            )->format('Y-m-d H:i:s'),
        ];
    }
}
