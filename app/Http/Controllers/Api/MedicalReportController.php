<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestRequest;
use App\Traits\ApiResponseTrait;
use App\Models\MedicalReport;
use App\Services\MedicalReportService;
use App\Http\Resources\MedicalReportResource;
use Illuminate\Http\Request;

class MedicalReportController extends Controller
{
    use ApiResponseTrait;

    protected MedicalReportService $service;

    public function __construct(
        MedicalReportService $service
    ) {
        $this->service = $service;
    }
    public function index(Request $request)
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $reports = $this->service->getAllReports(
            $filters['per_page'] ?? 15,
            $filters['search'] ?? null
        );

        return $this->successResponse(
            data: [
                'reports' => MedicalReportResource::collection(
                    $reports->items()
                ),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                ],
            ],
            message: 'Medical reports retrieved successfully.'
        );
    }


    public function show(
        MedicalReport $medicalReport
    ) {
        $report =
            $this->service
                ->getReportDetails($medicalReport);

        return $this->successResponse(
            new MedicalReportResource($report),
            "Medical report retrieved successfully."
        );
    }

    public function showByTestRequest(TestRequest $testRequest)
    {
        return $this->successResponse(
            new MedicalReportResource(
                $this->service->getByTestRequest($testRequest)
            ),
            'Medical report retrieved successfully.'
        );
    }

    public function generate(
        TestRequest $testRequest
    ) {
        $report =
            $this->service
                ->generate($testRequest);

        return $this->successResponse(
            new MedicalReportResource($report),
            "Medical report generated successfully."
        );
    }

    public function export()
    {

        return $this->service
            ->exportCsv();

    }

    public function notify(
        MedicalReport $medicalReport
    ) {

        $this->service
            ->notifyPatient($medicalReport);



        return $this->successResponse(
            null,
            "Patient notified successfully."
        );

    }

    public function exportPdf(
        MedicalReport $medicalReport
    ) {

        return $this->service
            ->exportPdf($medicalReport);

    }


}
