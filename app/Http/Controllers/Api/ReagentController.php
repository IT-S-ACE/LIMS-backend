<?php

namespace App\Http\Controllers\Api;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use App\Models\Reagent;
use App\Http\Requests\Reagent\StoreReagentRequest;
use App\Http\Requests\Reagent\UpdateReagentRequest;
use App\Http\Requests\Reagent\UpdateStockRequest;
use App\Http\Resources\ReagentResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReagentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected InventoryService $inventoryService
    ) {
    }


    public function index(Request $request): JsonResponse
    {
        try {

            $reagents = $this->inventoryService->getAll(
                search: $request->query('search'),
                perPage: (int) $request->query('per_page', 15)
            );

            return $this->successResponse(
                data: [
                    'reagents' => ReagentResource::collection($reagents->items()),
                    'pagination' => [
                        'current_page' => $reagents->currentPage(),
                        'last_page' => $reagents->lastPage(),
                        'per_page' => $reagents->perPage(),
                        'total' => $reagents->total(),
                    ],
                ],
                message: 'Reagents retrieved successfully.'
            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                $exception->getMessage()
            );

        }
    }


    public function store(
        StoreReagentRequest $request
    ): JsonResponse {

        try {

            $reagent = $this->inventoryService->store(
                $request->validated()
            );

            return $this->successResponse(

                data: new ReagentResource($reagent),

                message: 'Reagent created successfully.'

            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                $exception->getMessage()
            );

        }

    }

    public function show(
        Reagent $reagent
    ): JsonResponse {

        try {

            $reagent = $this->inventoryService
                ->show($reagent);

            return $this->successResponse(

                data: new ReagentResource($reagent),

                message: 'Reagent retrieved successfully.'

            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                $exception->getMessage()
            );

        }

    }

    public function update(
        UpdateReagentRequest $request,
        Reagent $reagent
    ): JsonResponse {

        try {

            $reagent = $this->inventoryService
                ->update(
                    $reagent,
                    $request->validated()
                );

            return $this->successResponse(

                data: new ReagentResource($reagent),

                message: 'Reagent updated successfully.'

            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                $exception->getMessage()
            );

        }

    }

    public function updateStock(
        UpdateStockRequest $request,
        Reagent $reagent
    ): JsonResponse {

        try {

            $reagent = $this->inventoryService
                ->updateStock(
                    $reagent,
                    $request->validated()
                );

            return $this->successResponse(

                data: new ReagentResource($reagent),

                message: 'Stock updated successfully.'

            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                $exception->getMessage()
            );

        }

    }


    public function destroy(
        Reagent $reagent
    ): JsonResponse {

        try {

            $this->inventoryService
                ->destroy($reagent);

            return $this->successResponse(

                message: 'Reagent deleted successfully.'

            );

        } catch (ValidationException $exception) {

            return $this->respondValidation($exception);

        } catch (Throwable $exception) {

            return $this->respondWithError(
                $exception->getMessage()
            );

        }

    }

}
