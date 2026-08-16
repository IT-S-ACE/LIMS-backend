<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordService
{
    public function __construct(
        protected OTPService $otpService,
        protected AuditLogService $auditLogs
    ) {
    }

    public function sendOTP(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user && $user->status === 'active') {
            $this->otpService->sendOTP($user, 'reset_password');
        }
    }

    public function verifyOTP(string $email, string $otp): bool
    {
        $user = User::query()
            ->where('email', $email)
            ->where('status', 'active')
            ->first();

        return $user
            ? $this->otpService->verifyOTP($user, 'reset_password', $otp)
            : false;
    }

    public function resetPassword(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()
                ->where('email', $data['email'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            $verifiedOtp = $user
                ? UserOtp::query()
                    ->where('user_id', $user->id)
                    ->where('type', 'reset_password')
                    ->whereNotNull('verified_at')
                    ->where('expires_at', '>', now())
                    ->latest()
                    ->lockForUpdate()
                    ->first()
                : null;

            if (!$user || !$verifiedOtp) {
                throw ValidationException::withMessages([
                    'email' => ['A verified password reset request is required.'],
                ]);
            }

            $user->update([
                'password' => Hash::make($data['password']),
            ]);

            $user->tokens()->delete();
            UserOtp::query()->where('user_id', $user->id)->delete();

            $this->auditLogs->record(
                'User',
                $user->id,
                'PASSWORD_RESET',
                null,
                ['sessions_revoked' => true],
                'Password reset completed after OTP verification',
                null,
                'SUCCESS',
                null,
                $user
            );

            return $user;
        });
    }
}
