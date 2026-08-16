<?php

namespace App\Http\Controllers\Api;

use Throwable;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use App\Services\PatientService;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\SearchPatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected PatientService $patientService
    ) {
    }

    public function index(
        SearchPatientRequest $request
    ): JsonResponse {
        try {

            $patients = $this->patientService
                ->getPatients($request->validated());

            return $this->successResponse(
                data: [
                    'patients' => PatientResource::collection(
                        $patients->items()
                    ),

                    'pagination' => [
                        'current_page' => $patients->currentPage(),
                        'last_page' => $patients->lastPage(),
                        'per_page' => $patients->perPage(),
                        'total' => $patients->total(),
                    ],
                ],
                message: 'Patients retrieved successfully.'
            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                message: $exception->getMessage()
            );

        }
    }


    public function show(
        string $id
    ): JsonResponse {
        try {

            $patient = $this->patientService
                ->getPatient($id);

            return $this->successResponse(
                data: new PatientResource($patient),
                message: 'Patient retrieved successfully.'
            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                message: $exception->getMessage()
            );

        }
    }

    public function store(
        StorePatientRequest $request
    ): JsonResponse {
        try {

            $patient = $this->patientService
                ->createPatient($request->validated());

            return $this->successResponse(
                data: new PatientResource($patient),
                message: 'Patient created successfully.'
            );

        } catch (Throwable $exception) {

            return $this->respondWithError(
                message: $exception->getMessage()
            );

        }
    }


    public function update(
        UpdatePatientRequest $request,
        string $id
    ): JsonResponse {
        try {

            $patient = $this->patientService
                ->updatePatient(
                    $id,
                    $request->validated()
                );

            return $this->successResponse(
                data: new PatientResource($patient),
                message: 'Patient updated successfully.'
            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                message: $exception->getMessage()
            );
        }
    }


    public function exportCsv()
    {
        return $this->patientService
            ->exportCsv();
    }


    public function destroy(
        string $id
    ): JsonResponse {

        try {

            $this->patientService
                ->deletePatient($id);

            return $this->successResponse(
                message: 'Patient deleted successfully.'
            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                message: $exception->getMessage()
            );

        }

    }
}
