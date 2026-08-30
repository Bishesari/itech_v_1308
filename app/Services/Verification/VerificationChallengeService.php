<?php

namespace App\Services\Verification;

use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Exceptions\Verification\ActiveVerificationChallengeException;
use App\Exceptions\Verification\SmsDeliveryException;
use App\Models\VerificationChallenge;
use Illuminate\Support\Facades\DB;

class VerificationChallengeService
{
    public function send(
        VerificationPurpose $purpose,
        string $firstNameFa,
        string $lastNameFa,
        NationalityType $nationalityType,
        string $identity,
        string $mobile,
        ?string $fingerprint = null,
        ?string $ip = null,
    ): VerificationChallenge {

        $challenge = DB::transaction(function () use (
            $purpose,
            $firstNameFa,
            $lastNameFa,
            $nationalityType,
            $identity,
            $mobile,
            $fingerprint,
            $ip,
        ) {
            $this->ensureCanSend($purpose, $mobile);

            return $this->createChallenge(
                $purpose,
                $firstNameFa,
                $lastNameFa,
                $nationalityType,
                $identity,
                $mobile,
                $fingerprint,
                $ip,
            );
        });

        try {
            $this->sendSms(
                $challenge->mobile,
                $challenge->verification_code,
            );
        } catch (\Throwable $e) {
            $challenge->delete();

            throw new SmsDeliveryException(
                previous: $e
            );
        }

        return $challenge;
    }

    private function ensureCanSend(
        VerificationPurpose $purpose,
        string $mobile,
    ): void {

        $exists = VerificationChallenge::query()
            ->active()
            ->where('purpose', $purpose)
            ->where('mobile', $mobile)
            ->exists();

        if ($exists) {
            throw new ActiveVerificationChallengeException;
        }
    }

    private function createChallenge(
        VerificationPurpose $purpose,
        string $firstNameFa,
        string $lastNameFa,
        NationalityType $nationalityType,
        string $identity,
        string $mobile,
        ?string $fingerprint,
        ?string $ip,
    ): VerificationChallenge {

        return VerificationChallenge::query()->create([

            'purpose' => $purpose,

            'first_name_fa' => $firstNameFa,
            'last_name_fa' => $lastNameFa,

            'nationality_type' => $nationalityType,

            'identity' => $identity,

            'mobile' => $mobile,

            'verification_code' => $this->generateVerificationCode(),

            'fingerprint' => $fingerprint,
            'ip' => $ip,

            'expires_at' => now()->addMinutes(
                config('verification.otp.expires_in')
            ),

        ]);
    }

    private function sendSms(
        string $mobile,
        string $verificationCode,
    ): void {

        // TODO:
        // SmsService::sendOtp($mobile, $verificationCode);
    }

    private function generateVerificationCode(): string
    {
        $length = config('verification.otp.length');

        $min = 10 ** ($length - 1);

        $max = (10 ** $length) - 1;

        return (string) random_int($min, $max);
    }
}
