<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Test\StoreTestRequest;
use App\Http\Requests\Test\UpdateTestRequest;
use App\Http\Resources\TestResource;
use App\Models\Test;
use App\Services\TestService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Validation\ValidationException;

class TestController extends Controller
{
    use ApiResponseTrait;

    protected TestService $service;

    public function __construct(
        TestService $service
    ) {
        $this->service = $service;
    }

    public function index(
        Request $request
    ): JsonResponse {

        try {

            $tests = $this->service->getTests(
                search: $request->query('search'),
                perPage: (int) $request->query(
                    'per_page',
                    15
                )
            );

            return $this->successResponse(
                data: [
                    'tests' => TestResource::collection(
                        $tests->items()
                    ),

                    'pagination' => [
                        'current_page' =>
                            $tests->currentPage(),

                        'last_page' =>
                            $tests->lastPage(),

                        'per_page' =>
                            $tests->perPage(),

                        'total' =>
                            $tests->total(),
                    ],
                ],

                message: 'Tests retrieved successfully.'
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
        StoreTestRequest $request
    ): JsonResponse {

        try {

            $test = $this->service->createTest(
                $request->validated()
            );

            return $this->successResponse(
                data: new TestResource($test),

                message: 'Test created successfully.'
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
        Test $test
    ): JsonResponse {

        try {

            $test = $this->service->getTest(
                $test
            );

            return $this->successResponse(
                data: new TestResource($test),

                message: 'Test retrieved successfully.'
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
        UpdateTestRequest $request,
        Test $test
    ): JsonResponse {

        try {

            $test = $this->service->updateTest(
                $test,
                $request->validated()
            );

            return $this->successResponse(
                data: new TestResource($test),

                message: 'Test updated successfully.'
            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                message: $exception->getMessage()
            );
        }
    }

    public function destroy(
        Test $test
    ): JsonResponse {

        try {

            $this->service->deleteTest(
                $test
            );

            return $this->successResponse(
                data: [],

                message: 'Test deleted successfully.'
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
