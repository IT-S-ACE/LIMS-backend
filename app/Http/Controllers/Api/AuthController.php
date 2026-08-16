<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\SendOTPRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Http\Resources\AuthResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {

            $this->authService->register($request->validated());

            return $this->respondWithSuccess(
                'Registration completed successfully. OTP has been sent to your email.'
            );

        } catch (ValidationException $e) {

            return $this->respondValidation($e);

        } catch (\Exception $e) {
            report($e);

            return $this->respondWithError('Registration could not be completed.');
        }
    }

    public function login(LoginRequest $request)
    {
        try {

            $this->authService->login($request->validated());

            return $this->respondWithSuccess(
                'OTP has been sent to your email.'
            );

        } catch (ValidationException $e) {

            return $this->respondValidation($e);

        } catch (ModelNotFoundException $e) {

            return $this->respondFirstOrFail($e);

        } catch (\Exception $e) {
            report($e);

            return $this->respondWithError('Login could not be completed.');
        }
    }

    public function verifyOTP(VerifyOTPRequest $request): JsonResponse
    {
        try {

            $result = $this->authService->verifyOTP(
                $request->validated()
            );

            return $this->successResponse(
                data: new AuthResource($result),
                message: 'OTP verified successfully.'
            );

        } catch (ValidationException $e) {

            return $this->respondValidation($e);

        } catch (ModelNotFoundException $e) {

            return $this->respondFirstOrFail($e);

        } catch (\Throwable $e) {
            report($e);

            return $this->respondWithError('OTP verification could not be completed.');
        }
    }

    public function resendOTP(
        SendOTPRequest $request
    ): JsonResponse {
        try {

            $this->authService->resendOTP(
                $request->validated()
            );

            return $this->respondWithSuccess(
                message: 'OTP resent successfully.'
            );

        } catch (ValidationException $e) {

            return $this->respondValidation($e);

        } catch (ModelNotFoundException $e) {

            return $this->respondFirstOrFail($e);

        } catch (\Throwable $e) {
            report($e);

            return $this->respondWithError('OTP could not be resent.');
        }
    }

}
