<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordOTPRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyResetPasswordRequest;
use App\Services\ResetPasswordService;
use App\Traits\ApiResponseTrait;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResetPasswordController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ResetPasswordService $resetPasswordService
    ) {
    }

    public function sendOTP(ResetPasswordOTPRequest $request)
    {
        try {
            $this->resetPasswordService->sendOTP($request->validated('email'));

            return $this->respondWithSuccess(
                'If the account exists, a password reset OTP has been sent.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->respondWithError(
                'The reset request could not be processed.'
            );
        }
    }

    public function verifyOTP(VerifyResetPasswordRequest $request)
    {
        try {
            $verified = $this->resetPasswordService->verifyOTP(
                $request->validated('email'),
                $request->validated('otp')
            );

            if (!$verified) {
                throw ValidationException::withMessages([
                    'otp' => ['Invalid or expired OTP.'],
                ]);
            }

            return $this->respondWithSuccess('OTP verified successfully.');
        } catch (ValidationException $exception) {
            return $this->respondValidation($exception);
        } catch (Throwable $exception) {
            report($exception);

            return $this->respondWithError(
                'The verification request could not be processed.'
            );
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->resetPasswordService->resetPassword($request->validated());

            return $this->respondWithSuccess('Password changed successfully.');
        } catch (ValidationException $exception) {
            return $this->respondValidation($exception);
        } catch (Throwable $exception) {
            report($exception);

            return $this->respondWithError(
                'The password could not be changed.'
            );
        }
    }
}
