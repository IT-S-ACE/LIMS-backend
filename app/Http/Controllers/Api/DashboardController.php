<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected DashboardService $service
    ) {
    }

    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'days' => ['nullable', 'integer', 'in:7,14,30'],
            ]);

            $dashboard = $this->service->getDashboard(
                $request->user(),
                (int) ($validated['days'] ?? 7)
            );

            return $this->successResponse(
                $dashboard,
                'Dashboard data retrieved successfully.'
            );
        } catch (ValidationException $exception) {
            return $this->respondValidation($exception);
        } catch (Throwable $exception) {
            report($exception);

            return $this->respondWithError(
                'Dashboard data could not be retrieved.'
            );
        }
    }

    public function indexsearch(Request $request)
    {
        try {
            $results = $this->service->search($request->get('q'));

            return $this->successResponse(
                $results,
                'Search results retrieved successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->respondWithError(
                'Search results could not be retrieved.'
            );
        }
    }
}
