<?php

namespace App\Services;

use App\Mail\SendOtpMail;
use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Support\Facades\Mail;

class OTPService
{
    private const MAX_ATTEMPTS = 5;

    public function generateOTP(User $user, string $type): UserOtp
    {
        UserOtp::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->delete();

        $code = $this->usesFixedTestingOtp($user)
            ? (string) config('auth.testing_otp.code')
            : (string) random_int(100000, 999999);

        $otp = UserOtp::create([
            'user_id' => $user->id,
            'otp' => '******',
            'otp_hash' => $this->hash($code),
            'attempts' => 0,
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        $otp->setAttribute('plain_code', $code);

        return $otp;
    }

    public function sendOTP(User $user, string $type): UserOtp
    {
        $otp = $this->generateOTP($user, $type);

        if (!$this->usesFixedTestingOtp($user)) {
            Mail::to($user->email)->send(
                new SendOtpMail(
                    $user,
                    (string) $otp->getAttribute('plain_code'),
                    $type
                )
            );
        }

        $otp->offsetUnset('plain_code');

        return $otp;
    }

    public function resendOTP(User $user, string $type): UserOtp
    {
        return $this->sendOTP($user, $type);
    }

    public function verifyOTP(User $user, string $type, string $code): bool
    {
        $otp = UserOtp::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$otp || $otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (!$otp->otp_hash || !hash_equals($otp->otp_hash, $this->hash($code))) {
            $otp->increment('attempts');

            if ($otp->fresh()->attempts >= self::MAX_ATTEMPTS) {
                $otp->update(['expires_at' => now()]);
            }

            return false;
        }

        $otp->update([
            'verified_at' => now(),
        ]);

        return true;
    }

    public function deleteOldOTP(User $user): void
    {
        UserOtp::query()->where('user_id', $user->id)->delete();
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function usesFixedTestingOtp(User $user): bool
    {
        if (!app()->environment(['local', 'testing'])) {
            return false;
        }

        if (!config('auth.testing_otp.enabled')) {
            return false;
        }

        $emails = array_map(
            fn($email) => mb_strtolower(trim((string) $email)),
            (array) config('auth.testing_otp.emails', [])
        );
        $legacyEmail = mb_strtolower(trim((string) config('auth.testing_otp.email')));
        if ($legacyEmail !== '') {
            $emails[] = $legacyEmail;
        }

        $emails = array_values(array_unique(array_filter($emails)));
        $code = (string) config('auth.testing_otp.code');
        $userEmail = mb_strtolower(trim($user->email));

        return $emails !== []
            && preg_match('/^\d{6}$/', $code) === 1
            && collect($emails)->contains(
                fn(string $email) => hash_equals($email, $userEmail)
            );
    }
}
