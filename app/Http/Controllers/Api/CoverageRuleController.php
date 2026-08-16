<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoverageRule\StoreCoverageRuleRequest;
use App\Http\Requests\CoverageRule\UpdateCoverageRuleRequest;
use App\Http\Resources\CoverageRuleResource;
use App\Models\CoverageRule;
use App\Services\CoverageRuleService;
use App\Traits\ApiResponseTrait;
use Throwable;


class CoverageRuleController extends Controller
{

    use ApiResponseTrait;


    protected CoverageRuleService $service;



    public function __construct(
        CoverageRuleService $service
    ) {
        $this->service = $service;
    }

    public function index()
    {
        $rules =
            CoverageRule::with(
                ['insuranceCompany', 'test'])
                ->latest()
                ->paginate(100);

        return $this->successResponse(
            [
                'rules' => CoverageRuleResource::collection($rules->items()),
                'pagination' => [
                    'current_page' => $rules->currentPage(),
                    'last_page' => $rules->lastPage(),
                    'per_page' => $rules->perPage(),
                    'total' => $rules->total(),
                ],
            ],
            "Coverage rules retrieved successfully."
        );
    }

    public function store(
        StoreCoverageRuleRequest $request
    ) {

        try {


            $rule =
                $this->service->create(
                    $request->validated()
                );



            return $this->successResponse(
                new CoverageRuleResource($rule),
                "Coverage rule created successfully."
            );


        } catch (Throwable $e) {

            return $this->respondWithError(
                $e->getMessage()
            );

        }

    }

    public function update(
        UpdateCoverageRuleRequest $request,
        CoverageRule $coverageRule
    ) {

        $rule =
            $this->service->update(
                $coverageRule,
                $request->validated()
            );



        return $this->successResponse(
            new CoverageRuleResource($rule),
            "Coverage rule updated successfully."
        );

    }

    public function destroy(
        CoverageRule $coverageRule
    ) {

        $this->service->delete($coverageRule);



        return $this->successResponse(
            null,
            "Coverage rule deleted successfully."
        );

    }

}
