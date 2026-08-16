<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sample\RegisterSampleRequest;
use App\Http\Requests\Sample\SampleDispositionRequest;
use App\Http\Requests\Sample\SearchSampleRequest;
use App\Http\Requests\Sample\UpdateSampleStatusRequest;
use App\Http\Resources\EnterResultResource;
use App\Http\Resources\SampleResource;
use App\Models\Sample;
use App\Services\SampleService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class SampleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected SampleService $service
    ) {
    }

    public function enterResult(Sample $sample): JsonResponse
    {
        return $this->successResponse(
            new EnterResultResource($this->service->enterResult($sample)),
            'Sample loaded successfully.'
        );
    }

    public function index(SearchSampleRequest $request): JsonResponse
    {
        $samples = $this->service->getSamples($request->validated());

        return $this->successResponse([
            'samples' => SampleResource::collection($samples->items()),
            'pagination' => [
                'current_page' => $samples->currentPage(),
                'last_page' => $samples->lastPage(),
                'per_page' => $samples->perPage(),
                'total' => $samples->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return $this->successResponse(
            new SampleResource($this->service->getSample($id))
        );
    }

    public function store(RegisterSampleRequest $request): JsonResponse
    {
        return $this->successResponse(
            new SampleResource($this->service->register($request->validated())),
            'Sample registered successfully.'
        );
    }

    public function updateStatus(
        UpdateSampleStatusRequest $request,
        Sample $sample
    ): JsonResponse {
        return $this->successResponse(
            new SampleResource(
                $this->service->updateStatus($sample, $request->validated('status'))
            ),
            'Sample status updated.'
        );
    }

    public function track(string $code): JsonResponse
    {
        return $this->successResponse(
            new SampleResource($this->service->track($code))
        );
    }

    public function reject(
        SampleDispositionRequest $request,
        Sample $sample
    ): JsonResponse {
        return $this->successResponse(
            new SampleResource(
                $this->service->reject($sample, $request->validated('reason'))
            ),
            'Sample rejected.'
        );
    }

    public function cancel(
        SampleDispositionRequest $request,
        Sample $sample
    ): JsonResponse {
        return $this->successResponse(
            new SampleResource(
                $this->service->cancel($sample, $request->validated('reason'))
            ),
            'Sample cancelled.'
        );
    }

    public function exportCsv()
    {
        return $this->service->exportCsv();
    }

    public function destroy(Sample $sample): JsonResponse
    {
        $this->service->destroy($sample);

        return $this->successResponse(
            message: 'Sample deleted successfully.'
        );
    }
}
