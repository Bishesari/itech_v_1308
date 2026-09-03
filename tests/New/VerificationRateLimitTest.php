<?php

use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Exceptions\Verification\SmsRateLimitException;
use App\Models\VerificationChallenge;
use App\Services\Verification\VerificationChallengeService;

it('rejects the fourth sms send for the same mobile within the rate limit window', function () {
    $service = app(VerificationChallengeService::class);

    $purpose = VerificationPurpose::Registration;
    $mobile = '09123456789';

    for ($i = 0; $i < 3; $i++) {
        $challenge = VerificationChallenge::factory()->create([
            'purpose' => $purpose,
            'first_name_fa' => 'علی',
            'last_name_fa' => 'احمدی',
            'nationality_type' => NationalityType::Iranian,
            'identity' => '2063531218',
            'mobile' => $mobile,
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'sms_sent_at' => now(),
        ]);
    }

    expect(fn() => $service->issue(
        purpose: $purpose,
        firstNameFa: 'علی',
        lastNameFa: 'احمدی',
        nationalityType: NationalityType::Iranian,
        identity: '2063531218',
        mobile: $mobile,
    ))->toThrow(SmsRateLimitException::class)
        ->and(
            VerificationChallenge::query()
                ->where('mobile', $mobile)
                ->count()
        )->toBe(3);

});
it('rejects the seventh sms send for the same fingerprint within the rate limit window', function () {
    $service = app(VerificationChallengeService::class);

    $purpose = VerificationPurpose::Registration;
    $mobile = '09123456789';
    $fingerprint = 'test-fingerprint';

    for ($i = 0; $i < 6; $i++) {
        VerificationChallenge::factory()->create([
            'purpose' => $purpose,
            'first_name_fa' => 'علی',
            'last_name_fa' => 'احمدی',
            'nationality_type' => NationalityType::Iranian,
            'identity' => '2063531218',
            'mobile' => $mobile . $i,
            'verification_code' => '123456',
            'fingerprint' => $fingerprint,
            'expires_at' => now()->subMinute(),
            'sms_sent_at' => now(),
        ]);
    }

    expect(fn() => $service->issue(
        purpose: $purpose,
        firstNameFa: 'علی',
        lastNameFa: 'احمدی',
        nationalityType: NationalityType::Iranian,
        identity: '2063531218',
        mobile: '09123456789',
        fingerprint: $fingerprint,
    ))->toThrow(SmsRateLimitException::class)
        ->and(
            VerificationChallenge::query()
                ->where('fingerprint', $fingerprint)
                ->count()
        )->toBe(6);

});
it('rejects the thirty-first sms send from the same ip within the rate limit window', function () {
    $service = app(VerificationChallengeService::class);

    $purpose = VerificationPurpose::Registration;
    $ip = '192.0.2.1';

    for ($i = 0; $i < 30; $i++) {
        VerificationChallenge::factory()->create([
            'purpose' => $purpose,
            'first_name_fa' => 'علی',
            'last_name_fa' => 'احمدی',
            'nationality_type' => NationalityType::Iranian,
            'identity' => str_pad((string) $i, 10, '0', STR_PAD_LEFT),
            'mobile' => '0912345' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'verification_code' => '123456',
            'ip' => $ip,
            'expires_at' => now()->subMinute(),
            'sms_sent_at' => now(),
        ]);
    }

    expect(fn() => $service->issue(
        purpose: $purpose,
        firstNameFa: 'علی',
        lastNameFa: 'احمدی',
        nationalityType: NationalityType::Iranian,
        identity: '2063531218',
        mobile: '09123456789',
        ip: $ip,
    ))->toThrow(SmsRateLimitException::class)
        ->and(
            VerificationChallenge::query()
                ->where('ip', $ip)
                ->count()
        )->toBe(30);

});
it('allows a new sms send when previous sends are outside the rate limit window', function () {
    $service = app(VerificationChallengeService::class);

    $purpose = VerificationPurpose::Registration;
    $mobile = '09123456789';

    for ($i = 0; $i < 3; $i++) {
        VerificationChallenge::factory()->create([
            'purpose' => $purpose,
            'first_name_fa' => 'علی',
            'last_name_fa' => 'احمدی',
            'nationality_type' => NationalityType::Iranian,
            'identity' => '206353121' . $i,
            'mobile' => $mobile,
            'verification_code' => '123456',
            'expires_at' => now()->subMinutes(11),
            'sms_sent_at' => now()->subMinutes(11),
        ]);
    }

    $challenge = $service->issue(
        purpose: $purpose,
        firstNameFa: 'علی',
        lastNameFa: 'احمدی',
        nationalityType: NationalityType::Iranian,
        identity: '2063531218',
        mobile: $mobile,
    );

    expect($challenge)->not->toBeNull()
        ->and($challenge->mobile)->toBe($mobile)
        ->and($challenge->sms_sent_at)->not->toBeNull()
        ->and(
            VerificationChallenge::query()
                ->where('mobile', $mobile)
                ->whereNotNull('sms_sent_at')
                ->count()
        )->toBe(4);

});
