<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialReport\FinancialReportRequest;
use App\Services\FinancialReportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected FinancialReportService $service)
    {
    }

    public function index(FinancialReportRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return $this->successResponse(
            $this->service->report($filters['from'], $filters['to']),
            'Financial report retrieved successfully.'
        );
    }

    public function export(FinancialReportRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $report = $this->service->report($filters['from'], $filters['to']);
        $filename = "financial-report-{$filters['from']}-to-{$filters['to']}.csv";

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, ['Financial Summary']);
            fputcsv($stream, ['Metric', 'Value']);
            foreach ($report['summary'] as $metric => $value) {
                fputcsv($stream, [$metric, $value]);
            }

            fputcsv($stream, []);
            fputcsv($stream, ['Billing Trend']);
            fputcsv($stream, ['Period', 'Gross Billed', 'Insurance Covered', 'Patient Due', 'Invoices']);
            foreach ($report['billing_trend'] as $row) {
                fputcsv($stream, [
                    $row['period'],
                    $row['gross_billed'],
                    $row['insurance_covered'],
                    $row['patient_due'],
                    $row['invoices_count'],
                ]);
            }

            fputcsv($stream, []);
            fputcsv($stream, ['Insurance Coverage by Company']);
            fputcsv($stream, ['Company', 'Invoices', 'Gross Billed', 'Insurance Covered', 'Patient Due', 'Coverage Rate %']);
            foreach ($report['coverage_by_company'] as $row) {
                fputcsv($stream, [
                    $row['company_name'],
                    $row['invoices_count'],
                    $row['gross_billed'],
                    $row['insurance_covered'],
                    $row['patient_due'],
                    $row['coverage_rate'],
                ]);
            }

            fputcsv($stream, []);
            fputcsv($stream, ['Top Tests']);
            fputcsv($stream, ['Test', 'Quantity', 'Gross Billed', 'Insurance Covered', 'Patient Due']);
            foreach ($report['top_tests'] as $row) {
                fputcsv($stream, [
                    $row['test_name'],
                    $row['quantity'],
                    $row['gross_billed'],
                    $row['insurance_covered'],
                    $row['patient_due'],
                ]);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
