<?php

namespace App\Traits;

use App\Enums\ResponseCode;
use App\Http\Resources\PaginationCollection;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HTTPCode;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ApiResponseTrait{
    protected function respondWithSuccess(string $message, string $code = ResponseCode::SUCCESS, int $statusCode = HTTPCode::HTTP_OK): JsonResponse
    {
        return $this->base(
            message: $message,
            code: $code,
            statusCode: $statusCode
        );
    }

    private function base(string $message, string $code, int $statusCode,   $data = []): JsonResponse
    {
        $body = [
            'code' => $code,
            'message' => $message,
            'server_time'   => Carbon::parse()->toDateTimeString(),
        ];
        if ($data instanceof PaginationCollection){

            $newData['payload'] = $data->collectionData;

            $newData['pagination'] = $data->pagination;

            $body = array_merge($body,$newData);

        }else{
            $body['payload'] = $data;
        }

        return response()->json($body, $statusCode);
    }
    protected function successResponse(
        $data = [],
        string $message = "Success",
        string $code = ResponseCode::SUCCESS
    ): JsonResponse {
        return $this->base(
            message: $message,
            code: $code,
            statusCode: HTTPCode::HTTP_OK,
            data: $data
        );
    }

    protected function respondValidation(ValidationException $e): JsonResponse
    {
        return $this->base(
            message: $e->getMessage(),
            code: ResponseCode::VALIDATION_ERROR,
            statusCode: HTTPCode::HTTP_BAD_REQUEST,
            data: [
                'errors' => $e->errors()
            ]
        );
    }

    protected function respondFirstOrFail(ModelNotFoundException $e): JsonResponse
    {


        $class =class_basename( $e->getModel());

        return $this->base(
            message: "The $class is not found",
            code: ResponseCode::MODEL_NOT_FOUND,
            statusCode: HTTPCode::HTTP_NOT_FOUND,
            data:[]
        );
    }

    protected function respondWithError(string $message, string $code = ResponseCode::GENERAL_ERROR, int $statusCode = HTTPCode::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->base(
            message: $message,
            code: $code,
            statusCode: $statusCode,
        );
    }
}
