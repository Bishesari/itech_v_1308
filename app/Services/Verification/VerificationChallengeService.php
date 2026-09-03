<?php

namespace App\Services\Verification;

use App\Contracts\SmsGateway;
use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Exceptions\Verification\ActiveVerificationChallengeException;
use App\Exceptions\Verification\ExpiredVerificationChallengeException;
use App\Exceptions\Verification\InvalidVerificationCodeException;
use App\Exceptions\Verification\SmsDeliveryException;
use App\Exceptions\Verification\SmsRateLimitException;
use App\Exceptions\Verification\VerificationAttemptsExceededException;
use App\Exceptions\Verification\VerificationChallengeNotFoundException;
use App\Models\VerificationChallenge;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class VerificationChallengeService
{
    /**
     * Get the active challenge or create a new one.
     *
     * If an active challenge already exists for the mobile/purpose,
     * it will be returned without sending another SMS.
     */

    /**
     * Send SMS through the configured SMS provider.
     */
    public function __construct(
        private readonly SmsGateway $smsGateway,
    ) {}

    public function issue(
        VerificationPurpose $purpose,
        string $firstNameFa,
        string $lastNameFa,
        NationalityType $nationalityType,
        string $identity,
        string $mobile,
        ?string $fingerprint = null,
        ?string $ip = null,
    ): VerificationChallenge {
        if ($challenge = $this->findActiveChallenge($purpose, $mobile)) {
            return $challenge;
        }
        $this->ensureCanSendSms($purpose, $mobile);
        $this->ensureCanSendByFingerprint($purpose, $fingerprint);
        $this->ensureCanSendByIp($purpose, $ip);

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
            return $this->createChallenge(
                purpose: $purpose,
                firstNameFa: $firstNameFa,
                lastNameFa: $lastNameFa,
                nationalityType: $nationalityType,
                identity: $identity,
                mobile: $mobile,
                fingerprint: $fingerprint,
                ip: $ip,
            );
        });

        $this->dispatchSms($challenge);

        return $challenge;
    }

    /**
     * Replace the current challenge with a new one and send a new OTP.
     */
    public function resend(
        VerificationPurpose $purpose,
        string $mobile,
        ?string $fingerprint = null,
        ?string $ip = null,
    ): VerificationChallenge {
        $challenge = DB::transaction(function () use (
            $purpose,
            $mobile,
            $fingerprint,
            $ip,
        ) {
            $current = $this->findLatestChallenge($purpose, $mobile);

            if (! $current) {
                throw new VerificationChallengeNotFoundException;
            }

            if ($current->expires_at->isFuture()) {
                throw new ActiveVerificationChallengeException;
            }

            $this->ensureCanSendSms($purpose, $mobile);
            $this->ensureCanSendByFingerprint($purpose, $fingerprint);
            $this->ensureCanSendByIp($purpose, $ip);

            $challenge = $this->createChallenge(
                purpose: $current->purpose,
                firstNameFa: $current->first_name_fa,
                lastNameFa: $current->last_name_fa,
                nationalityType: $current->nationality_type,
                identity: $current->identity,
                mobile: $current->mobile,
                fingerprint: $fingerprint,
                ip: $ip,
            );

            return $challenge;
        });
        $this->dispatchSms($challenge);

        return $challenge;
    }

    public function verify(
        VerificationPurpose $purpose,
        string $mobile,
        string $verificationCode,
    ): VerificationChallenge {
        $challenge = $this->findLatestChallenge($purpose, $mobile);

        if (! $challenge) {
            throw new VerificationChallengeNotFoundException;
        }

        if ($challenge->expires_at->isPast()) {
            throw new ExpiredVerificationChallengeException;
        }

        if (
            $challenge->attempts >= config('verification.otp.max_attempts')
        ) {
            throw new VerificationAttemptsExceededException;
        }

        if (! hash_equals($challenge->verification_code, $verificationCode)) {
            $challenge->increment('attempts');

            throw new InvalidVerificationCodeException;
        }

        return $challenge->fresh();
    }

    /**
     * Find the currently active challenge.
     */
    private function findActiveChallenge(
        VerificationPurpose $purpose,
        string $mobile,
    ): ?VerificationChallenge {
        return VerificationChallenge::query()
            ->active()
            ->where('purpose', $purpose)
            ->where('mobile', $mobile)
            ->latest('id')
            ->first();
    }

    private function findLatestChallenge(
        VerificationPurpose $purpose,
        string $mobile,
    ): ?VerificationChallenge {
        return VerificationChallenge::query()
            ->where('purpose', $purpose)
            ->where('mobile', $mobile)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();
    }

    /**
     * Create a new verification challenge.
     */
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
            'first_name_fa' => $firstNameFa,
            'last_name_fa' => $lastNameFa,

            'nationality_type' => $nationalityType,

            'identity' => $identity,

            'mobile' => $mobile,

            'purpose' => $purpose,

            'verification_code' => $this->generateVerificationCode(),

            'fingerprint' => $fingerprint,
            'ip' => $ip,

            'expires_at' => $this->expiresAt(),
        ]);
    }

    /**
     * Send the OTP SMS.
     */
    private function dispatchSms(
        VerificationChallenge $challenge,
    ): void {
        try {
            $this->smsGateway->sendOtp(
                $challenge->mobile,
                $challenge->verification_code,
            );

            $challenge->update([
                'sms_sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            throw new SmsDeliveryException(
                previous: $e,
            );
        }
    }

    /**
     * Generate OTP expiration timestamp.
     */
    private function expiresAt(): CarbonInterface
    {
        return now()->addMinutes(
            config('verification.otp.expires_in')
        );
    }

    /**
     * Generate a numeric OTP.
     */
    private function generateVerificationCode(): string
    {
        $length = config('verification.otp.length');

        $min = 10 ** ($length - 1);

        $max = (10 ** $length) - 1;

        return (string) random_int($min, $max);
    }

    private function ensureCanSendSms(
        VerificationPurpose $purpose,
        string $mobile,
    ): void {
        $count = VerificationChallenge::query()
            ->where('purpose', $purpose)
            ->where('mobile', $mobile)
            ->whereNotNull('sms_sent_at')
            ->where(
                'sms_sent_at',
                '>=',
                now()->subMinutes(
                    config('verification.otp.send_window')
                )
            )
            ->count();

        if ($count >= config('verification.otp.max_sends')) {
            throw new SmsRateLimitException;
        }
    }

    private function ensureCanSendByFingerprint(
        VerificationPurpose $purpose,
        ?string $fingerprint,
    ): void {
        if ($fingerprint === null) {
            return;
        }

        $count = VerificationChallenge::query()
            ->where('purpose', $purpose)
            ->where('fingerprint', $fingerprint)
            ->whereNotNull('sms_sent_at')
            ->where(
                'sms_sent_at',
                '>=',
                now()->subMinutes(
                    config('verification.otp.fingerprint_send_window')
                )
            )
            ->count();

        if ($count >= config('verification.otp.fingerprint_max_sends')) {
            throw new SmsRateLimitException;
        }
    }

    private function ensureCanSendByIp(
        VerificationPurpose $purpose,
        ?string $ip,
    ): void {
        if ($ip === null) {
            return;
        }

        $count = VerificationChallenge::query()
            ->where('purpose', $purpose)
            ->where('ip', $ip)
            ->whereNotNull('sms_sent_at')
            ->where(
                'sms_sent_at',
                '>=',
                now()->subMinutes(
                    config('verification.otp.ip_send_window')
                )
            )
            ->count();

        if ($count >= config('verification.otp.ip_max_sends')) {
            throw new SmsRateLimitException;
        }
    }
}
