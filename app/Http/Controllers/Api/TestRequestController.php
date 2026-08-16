<?php

namespace App\Http\Controllers\Api;

use Throwable;
use App\Models\TestRequest;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponseTrait;
use App\Services\TestRequestService;
use App\Http\Controllers\Controller;
use App\Http\Resources\TestRequestResource;
use App\Http\Requests\TestRequest\StoreTestRequestRequest;
use App\Http\Requests\TestRequest\UpdateTestRequestRequest;
use App\Http\Requests\TestRequest\SearchTestRequestRequest;
use Illuminate\Validation\ValidationException;

class TestRequestController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected TestRequestService $testRequestService
    ) {
    }

    public function index(
        SearchTestRequestRequest $request
    ): JsonResponse {
        try {

            $testRequests = $this
                ->testRequestService
                ->getTestRequests(
                    $request->validated()
                );

            return $this->successResponse(
                data: [
                    'test_requests' =>
                        TestRequestResource::collection(
                            $testRequests->items()
                        ),

                    'pagination' => [
                        'current_page' =>
                            $testRequests->currentPage(),

                        'last_page' =>
                            $testRequests->lastPage(),

                        'per_page' =>
                            $testRequests->perPage(),

                        'total' =>
                            $testRequests->total(),
                    ],
                ],
                message:
                'Test requests retrieved successfully.'
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

            $testRequest = $this
                ->testRequestService
                ->getTestRequest($id);

            return $this->successResponse(
                data: new TestRequestResource(
                    $testRequest
                ),
                message:
                'Test request retrieved successfully.'
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
        StoreTestRequestRequest $request
    ): JsonResponse {
        try {

            $testRequest = $this
                ->testRequestService
                ->createTestRequest(
                    $request->validated()
                );

            return $this->successResponse(
                data: new TestRequestResource(
                    $testRequest
                ),
                message:
                'Test request created successfully.'
            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                message: $exception->getMessage()
            );

        }
    }


    public function update(
        UpdateTestRequestRequest $request,
        TestRequest $testRequest
    ): JsonResponse {
        try {

            $testRequest = $this
                ->testRequestService
                ->updateTestRequest(
                    $testRequest,
                    $request->validated()
                );

            return $this->successResponse(
                data: new TestRequestResource(
                    $testRequest
                ),
                message:
                'Test request updated successfully.'
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
        return $this->testRequestService
            ->exportCsv();
    }


    public function destroy(
        TestRequest $testRequest
    ): JsonResponse {

        try {

            $this->testRequestService
                ->destroy($testRequest);

            return $this->successResponse(

                message:
                'Test request deleted successfully.'

            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(

                message:
                $exception->getMessage()

            );

        }

    }
}
