<?php

namespace App\Services;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected OTPService $otpService;

    public function __construct(
        OTPService $otpService,
        protected AuditLogService $auditLogs
    )
    {
        $this->otpService = $otpService;
    }

    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'patient',
                'status' => 'active',
            ]);

            Patient::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'dob' => $data['dob'],
            ]);

            $this->otpService->sendOTP(
                $user,
                'register'
            );

            return $user;
        });
    }

    public function login(array $data): User
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.']
            ]);
        }

        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Invalid credentials.']
            ]);
        }

        $this->otpService->sendOTP($user, 'login');

        return $user;
    }

    public function verifyOTP(array $data): array
    {
        $user = User::query()
            ->where('email', $data['email'])
            ->where('status', 'active')
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP']
            ]);
        }

        $verified = $this->otpService->verifyOTP(
            $user,
            $data['type'],
            $data['otp']
        );

        if (!$verified) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP']
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->auditLogs->record(
            'User',
            $user->id,
            'LOGIN',
            null,
            ['authentication' => 'otp'],
            'User authenticated successfully',
            null,
            'SUCCESS',
            null,
            $user
        );

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function resendOTP(array $data): void
    {
        $user = User::query()
            ->where('email', $data['email'])
            ->where('status', 'active')
            ->first();

        if ($user) {
            $this->otpService->resendOTP(
                $user,
                $data['type']
            );
        }
    }
}
