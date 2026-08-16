<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\ApplyInsuranceRequest;
use App\Http\Resources\InsuranceCoverageResource;
use App\Models\TestRequest;
use App\Services\InsuranceService;
use App\Traits\ApiResponseTrait;



class InsuranceController extends Controller
{

    use ApiResponseTrait;


    protected InsuranceService $service;



    public function __construct(
        InsuranceService $service
    ) {
        $this->service = $service;
    }

    public function apply(
        ApplyInsuranceRequest $request,
        TestRequest $testRequest
    ) {

        $result =
            $this->service
                ->applyInsurance(
                    $testRequest,
                    $request->validated()
                );

        return $this->successResponse(

            new InsuranceCoverageResource($result),

            "Insurance applied successfully."

        );

    }





    /*
    |--------------------------------------------------------------------------
    | UC-30 Calculate Insurance Coverage
    |--------------------------------------------------------------------------
    */


    public function calculate(
        TestRequest $testRequest
    ) {

        $result =
            $this->service
                ->calculateCoverage(
                    $testRequest
                );



        return $this->successResponse(

            new InsuranceCoverageResource($result),

            "Insurance coverage calculated successfully."

        );

    }


}