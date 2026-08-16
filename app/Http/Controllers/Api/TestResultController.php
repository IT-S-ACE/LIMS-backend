<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestResult\ReturnTestResultRequest;
use App\Http\Requests\TestResult\ReviewTestResultRequest;
use App\Http\Requests\TestResult\SaveSampleResultsRequest;
use App\Http\Resources\EnterResultResource;
use App\Http\Resources\TestResultResource;
use App\Models\Sample;
use App\Models\TestResult;
use App\Services\TestResultService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestResultController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected TestResultService $service)
    {
    }

    public function list(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                'in:draft,pending_review,reviewed,correction_required,approved',
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $results = $this->service->getResults($filters);

        return $this->successResponse([
            'results' => TestResultResource::collection($results->items()),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    public function show(TestResult $testResult): JsonResponse
    {
        return $this->successResponse(
            new TestResultResource($this->service->getResult($testResult))
        );
    }

    public function workspace(Sample $sample): JsonResponse
    {
        return $this->successResponse(
            new EnterResultResource($this->service->getEntryWorkspace($sample))
        );
    }

    public function saveSampleResults(
        SaveSampleResultsRequest $request,
        Sample $sample
    ): JsonResponse {
        $results = $this->service->saveSampleResults(
            $sample,
            $request->validated(),
            $request->user()
        );

        return $this->successResponse(
            TestResultResource::collection($results),
            'Result draft saved successfully.'
        );
    }

    public function submitSampleResults(Request $request, Sample $sample): JsonResponse
    {
        abort_unless(
            in_array($request->user()?->role, ['admin', 'lab_technician'], true),
            403,
            'Unauthorized.'
        );

        return $this->successResponse(
            TestResultResource::collection(
                $this->service->submitSampleResults($sample, $request->user())
            ),
            'Results submitted for medical review.'
        );
    }

    public function review(
        ReviewTestResultRequest $request,
        TestResult $testResult
    ): JsonResponse {
        return $this->successResponse(
            new TestResultResource(
                $this->service->review(
                    $testResult,
                    $request->validated('notes'),
                    $request->user()
                )
            ),
            'Result reviewed successfully.'
        );
    }

    public function returnForCorrection(
        ReturnTestResultRequest $request,
        TestResult $testResult
    ): JsonResponse {
        return $this->successResponse(
            new TestResultResource(
                $this->service->returnForCorrection(
                    $testResult,
                    $request->validated('reason'),
                    $request->user()
                )
            ),
            'Result returned for correction.'
        );
    }

    public function approve(Request $request, TestResult $testResult): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Unauthorized.');

        return $this->successResponse(
            new TestResultResource(
                $this->service->approve($testResult, $request->user())
            ),
            'Result approved and locked.'
        );
    }

    public function index(Sample $sample): JsonResponse
    {
        return $this->successResponse(
            TestResultResource::collection($this->service->allBySample($sample))
        );
    }

    public function exportCsv()
    {
        return $this->service->exportCsv();
    }
}
