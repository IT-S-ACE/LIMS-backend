<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInsuranceCompanyRequest;
use App\Http\Requests\Insurance\UpdateInsuranceCompanyRequest;
use App\Http\Resources\InsuranceCompanyResource;
use App\Models\InsuranceCompany;
use App\Services\InsuranceCompanyService;
use App\Traits\ApiResponseTrait;
use Throwable;


class InsuranceCompanyController extends Controller
{

    use ApiResponseTrait;


    protected InsuranceCompanyService $service;


    public function __construct(
        InsuranceCompanyService $service
    ) {
        $this->service = $service;
    }



    public function index()
    {
        $query = InsuranceCompany::query()->orderBy('name');

        if (request()->user()?->role !== 'admin') {
            $query->where('status', 'approved');
        }

        $companies = $query->paginate(100);

        return $this->successResponse(
            [
                'companies' => InsuranceCompanyResource::collection($companies->items()),
                'pagination' => [
                    'current_page' => $companies->currentPage(),
                    'last_page' => $companies->lastPage(),
                    'per_page' => $companies->perPage(),
                    'total' => $companies->total(),
                ],
            ],
            "Insurance companies retrieved successfully."
        );

    }




    public function store(
        StoreInsuranceCompanyRequest $request
    ) {

        try {


            $company =
                $this->service->create(
                    $request->validated()
                );


            return $this->successResponse(
                new InsuranceCompanyResource($company),
                "Insurance company created successfully."
            );


        } catch (Throwable $e) {

            return $this->respondWithError(
                $e->getMessage()
            );

        }

    }





    public function update(
        UpdateInsuranceCompanyRequest $request,
        InsuranceCompany $insuranceCompany
    ) {

        $company =
            $this->service->update(
                $insuranceCompany,
                $request->validated()
            );


        return $this->successResponse(
            new InsuranceCompanyResource($company),
            "Insurance company updated successfully."
        );

    }





    public function destroy(
        InsuranceCompany $insuranceCompany
    ) {

        $this->service->delete($insuranceCompany);


        return $this->successResponse(
            null,
            "Insurance company deleted successfully."
        );

    }

}
