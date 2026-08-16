<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\TestRequest;
use App\Models\MedicalReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;



class MedicalReportService
{



    public function getReportDetails(
        MedicalReport $report
    ) {
        return $report->load([
            'testRequest.patient',
            'testRequest.invoice',
            'testRequest.items.test',
            'testRequest.samples.testResults.testRequestItem.test',
            'testRequest.samples.testResults.enteredBy',
            'testRequest.samples.testResults.reviewedBy',
            'testRequest.samples.testResults.approvedBy',
        ]);
    }

    public function getByTestRequest(TestRequest $testRequest): MedicalReport
    {
        $report = $testRequest->medicalReport;

        if (!$report || $testRequest->status !== 'completed') {
            throw ValidationException::withMessages([
                'report' => ['The medical report is available after all results are approved.'],
            ]);
        }

        return $this->getReportDetails($report);
    }

    public function generate(TestRequest $testRequest): MedicalReport
    {
        return DB::transaction(function () use ($testRequest) {

            if ($testRequest->medicalReport) {
                return $testRequest->medicalReport;

            }

            if (
                $testRequest->status !== 'completed'
                || !$testRequest->results()->exists()
                || $testRequest->results()->where('workflow_status', '!=', 'approved')->exists()
            ) {
                throw ValidationException::withMessages([
                    'report' => [
                        'The official report can only be generated after all results are approved.',
                    ],
                ]);
            }


            $testRequest->load([
                'patient',
                'invoice',
                'samples.testResults.testRequestItem.test',
                'samples.testResults.enteredBy',
                'samples.testResults.reviewedBy',
                'samples.testResults.approvedBy',
            ]);


            $pdf = Pdf::loadView(
                'reports.medical-report',
                [
                    'testRequest' => $testRequest
                ]
            );


            $fileName =
                'reports/' .
                $testRequest->id .
                '.pdf';


            Storage::disk('public')
                ->put(
                    $fileName,
                    $pdf->output()
                );


            return MedicalReport::create([

                'test_request_id' => $testRequest->id,

                'pdf_path' => $fileName,

                'generated_at' => now()

            ]);

        });
    }
    public function exportPdf(
        MedicalReport $medicalReport
    ) {
        if (
            !Storage::disk('public')
                ->exists($medicalReport->pdf_path)
        ) {
            throw ValidationException::withMessages([
                'pdf' => [
                    'PDF file not found.'
                ]
            ]);
        }
        return Storage::disk('public')
            ->download(
                $medicalReport->pdf_path
            );
    }

    public function getAllReports(
        int $perPage = 15,
        ?string $search = null
    ) {

        return MedicalReport::query()

            ->with([
                'testRequest.patient',
                'testRequest.invoice',
                'testRequest.items.test',
                'testRequest.samples.testResults.testRequestItem.test',
                'testRequest.samples.testResults.enteredBy',
                'testRequest.samples.testResults.reviewedBy',
                'testRequest.samples.testResults.approvedBy',
            ])

            ->when($search, function ($query) use ($search) {
                $query->whereHas('testRequest', function ($query) use ($search) {
                    $query
                        ->where('request_number', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('patient_number', 'like', "%{$search}%");
                        });
                });
            })

            ->latest()

            ->paginate($perPage);

    }

    public function notifyPatient(
        MedicalReport $medicalReport
    ) {


        $medicalReport->load(
            'testRequest.patient'
        );


        $patient =
            $medicalReport
                ->testRequest
                ->patient;



        Notification::create([

            'patient_id' => $patient->id,

            'message' => 'Your medical report is ready.',

            'status' => 'sent'

        ]);


    }

    public function exportCsv(): StreamedResponse
    {


        $reports =
            MedicalReport::with([
                'testRequest.patient',
                'testRequest.items'
            ])
                ->get();



        return response()->streamDownload(function () use ($reports) {


            $handle = fopen('php://output', 'w');



            fputcsv($handle, [

                'Request ID',
                'Patient',
                'Tests Count',
                'Generated Date',
                'Status'

            ]);



            foreach ($reports as $report) {

                fputcsv($handle, [

                    $report->testRequest->id,


                    $report
                        ->testRequest
                        ->patient
                        ->name,


                    $report
                        ->testRequest
                        ->items
                        ->count(),


                    $report->generated_at,


                    $report
                        ->testRequest
                        ->status

                ]);

            }



            fclose($handle);



        }, 'medical-reports.csv');

    }

}
