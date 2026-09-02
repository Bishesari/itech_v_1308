<?php

namespace Tests\Feature\Verification;

use App\Contracts\SmsGateway;
use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Exceptions\Verification\ActiveVerificationChallengeException;
use App\Exceptions\Verification\SmsDeliveryException;
use App\Exceptions\Verification\SmsRateLimitException;
use App\Exceptions\Verification\VerificationChallengeNotFoundException;
use App\Models\VerificationChallenge;
use App\Services\Verification\VerificationChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VerificationChallengeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_creates_challenge_and_sends_sms(): void
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->once()
                ->with('09123456789', Mockery::type('string'));
        });

        $service = app(VerificationChallengeService::class);

        $challenge = $service->issue(
            purpose: VerificationPurpose::Registration,
            firstNameFa: 'علی',
            lastNameFa: 'احمدی',
            nationalityType: NationalityType::Iranian,
            identity: '1234567890',
            mobile: '09123456789',
        );

        $this->assertDatabaseHas('verification_challenges', [
            'id' => $challenge->id,
            'mobile' => '09123456789',
            'purpose' => VerificationPurpose::Registration->value,
            'first_name_fa' => 'علی',
            'last_name_fa' => 'احمدی',
            'identity' => '1234567890',
        ]);

        $this->assertNotNull($challenge->sms_sent_at);
        $this->assertNotNull($challenge->expires_at);
    }

    public function test_issue_returns_existing_active_challenge_without_sending_new_sms(): void
    {
        $existing = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'expires_at' => now()->addMinute(),
            'sms_sent_at' => now(),
            'verified_at' => null,
        ]);

        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldNotReceive('sendOtp');
        });

        $service = app(VerificationChallengeService::class);

        $challenge = $service->issue(
            purpose: VerificationPurpose::Registration,
            firstNameFa: 'علی',
            lastNameFa: 'احمدی',
            nationalityType: NationalityType::Iranian,
            identity: '1234567890',
            mobile: '09123456789',
        );

        $this->assertSame($existing->id, $challenge->id);
    }

    public function test_issue_creates_new_challenge_when_previous_challenge_is_expired(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'expires_at' => now()->subMinute(),
            'sms_sent_at' => now(),
            'verified_at' => null,
        ]);

        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->once()
                ->with('09123456789', Mockery::type('string'));
        });

        $service = app(VerificationChallengeService::class);

        $challenge = $service->issue(
            purpose: VerificationPurpose::Registration,
            firstNameFa: 'علی',
            lastNameFa: 'احمدی',
            nationalityType: NationalityType::Iranian,
            identity: '1234567890',
            mobile: '09123456789',
        );

        $this->assertDatabaseCount('verification_challenges', 2);
        $this->assertTrue($challenge->expires_at->isFuture());
        $this->assertNotNull($challenge->sms_sent_at);
    }

    public function test_resend_throws_when_there_is_no_unverified_challenge(): void
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldNotReceive('sendOtp');
        });

        $service = app(VerificationChallengeService::class);

        $this->expectException(VerificationChallengeNotFoundException::class);

        $service->resend(
            purpose: VerificationPurpose::Registration,
            mobile: '09123456789',
        );
    }

    public function test_resend_throws_when_current_challenge_is_still_active(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'expires_at' => now()->addMinute(),
            'sms_sent_at' => now(),
            'verified_at' => null,
        ]);

        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldNotReceive('sendOtp');
        });

        $service = app(VerificationChallengeService::class);

        $this->expectException(ActiveVerificationChallengeException::class);

        $service->resend(
            purpose: VerificationPurpose::Registration,
            mobile: '09123456789',
        );
    }

    public function test_resend_creates_new_challenge_sends_new_otp_and_keeps_old_challenge(): void
    {
        $old = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'expires_at' => now()->subMinute(),
            'sms_sent_at' => now()->subMinute(),
            'verified_at' => null,
        ]);

        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->once()
                ->with('09123456789', Mockery::type('string'));
        });

        $service = app(VerificationChallengeService::class);

        $new = $service->resend(
            purpose: VerificationPurpose::Registration,
            mobile: '09123456789',
        );

        $this->assertNotSame($old->id, $new->id);

        $this->assertDatabaseHas('verification_challenges', [
            'id' => $old->id,
        ]);

        $this->assertDatabaseHas('verification_challenges', [
            'id' => $new->id,
            'mobile' => '09123456789',
        ]);

        $this->assertNotNull($new->sms_sent_at);
        $this->assertTrue($new->expires_at->isFuture());
    }

    public function test_sms_failure_throws_exception_and_challenge_is_kept_without_sms_sent_at(): void
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->once()
                ->andThrow(new \RuntimeException('SMS provider failed'));
        });

        $service = app(VerificationChallengeService::class);

        $this->expectException(SmsDeliveryException::class);

        try {
            $service->issue(
                purpose: VerificationPurpose::Registration,
                firstNameFa: 'علی',
                lastNameFa: 'احمدی',
                nationalityType: NationalityType::Iranian,
                identity: '1234567890',
                mobile: '09123456789',
            );
        } finally {
            $challenge = VerificationChallenge::query()->latest('id')->first();

            $this->assertNotNull($challenge);
            $this->assertNull($challenge->sms_sent_at);
        }
    }

    public function test_sms_rate_limit_blocks_after_three_successful_sms_in_ten_minutes(): void
    {
        VerificationChallenge::factory()->count(3)->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'sms_sent_at' => now()->subMinutes(2),
            'expires_at' => now()->subMinutes(1),
            'verified_at' => null,
        ]);

        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldNotReceive('sendOtp');
        });

        $service = app(VerificationChallengeService::class);

        $this->expectException(SmsRateLimitException::class);

        $service->issue(
            purpose: VerificationPurpose::Registration,
            firstNameFa: 'علی',
            lastNameFa: 'احمدی',
            nationalityType: NationalityType::Iranian,
            identity: '1234567890',
            mobile: '09123456789',
        );
    }

    public function test_failed_sms_does_not_count_toward_sms_rate_limit(): void
    {
        VerificationChallenge::factory()->count(2)->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'sms_sent_at' => now()->subMinutes(2),
            'expires_at' => now()->subMinutes(1),
            'verified_at' => null,
        ]);

        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'sms_sent_at' => null,
            'expires_at' => now()->subMinute(),
            'verified_at' => null,
        ]);

        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->once()
                ->with('09123456789', Mockery::type('string'));
        });

        $service = app(VerificationChallengeService::class);

        $challenge = $service->issue(
            purpose: VerificationPurpose::Registration,
            firstNameFa: 'علی',
            lastNameFa: 'احمدی',
            nationalityType: NationalityType::Iranian,
            identity: '1234567890',
            mobile: '09123456789',
        );

        $this->assertNotNull($challenge->sms_sent_at);
    }

    public function test_sms_outside_ten_minute_window_does_not_count_toward_rate_limit(): void
    {
        VerificationChallenge::factory()->count(3)->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'sms_sent_at' => now()->subMinutes(11),
            'expires_at' => now()->subMinutes(10),
            'verified_at' => null,
        ]);

        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->once()
                ->with('09123456789', Mockery::type('string'));
        });

        $service = app(VerificationChallengeService::class);

        $challenge = $service->issue(
            purpose: VerificationPurpose::Registration,
            firstNameFa: 'علی',
            lastNameFa: 'احمدی',
            nationalityType: NationalityType::Iranian,
            identity: '1234567890',
            mobile: '09123456789',
        );

        $this->assertNotNull($challenge->sms_sent_at);
    }
    public function test_blocks_sms_after_six_successful_sends_from_the_same_fingerprint(): void
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->times(6);
        });

        $service = app(VerificationChallengeService::class);

        $fingerprint = 'test-fingerprint-123';

        for ($i = 0; $i < 6; $i++) {
            $mobile = '0912000000' . $i;

            $service->issue(
                purpose: VerificationPurpose::Registration,
                firstNameFa: 'علی',
                lastNameFa: 'احمدی',
                nationalityType: NationalityType::Iranian,
                identity: '001234567' . $i,
                mobile: $mobile,
                fingerprint: $fingerprint,
                ip: '127.0.0.1',
            );
        }

        $this->expectException(SmsRateLimitException::class);

        $service->issue(
            purpose: VerificationPurpose::Registration,
            firstNameFa: 'علی',
            lastNameFa: 'احمدی',
            nationalityType: NationalityType::Iranian,
            identity: '0012345680',
            mobile: '09129999999',
            fingerprint: $fingerprint,
            ip: '127.0.0.1',
        );
    }

    public function test_failed_sms_does_not_count_toward_fingerprint_rate_limit(): void
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->times(7)
                ->andReturnUsing(function () {
                    static $attempt = 0;

                    $attempt++;

                    if ($attempt === 1) {
                        throw new \RuntimeException('SMS failed');
                    }
                });
        });

        $service = app(VerificationChallengeService::class);

        $fingerprint = 'test-fingerprint-456';

        try {
            $service->issue(
                purpose: VerificationPurpose::Registration,
                firstNameFa: 'علی',
                lastNameFa: 'احمدی',
                nationalityType: NationalityType::Iranian,
                identity: '0012345678',
                mobile: '09120000001',
                fingerprint: $fingerprint,
                ip: '127.0.0.1',
            );
        } catch (SmsDeliveryException $e) {
            // Expected: first SMS fails and must not count toward the rate limit.
        }

        for ($i = 0; $i < 6; $i++) {
            $service->issue(
                purpose: VerificationPurpose::Registration,
                firstNameFa: 'علی',
                lastNameFa: 'احمدی',
                nationalityType: NationalityType::Iranian,
                identity: '00123456' . (80 + $i),
                mobile: '091200000' . (2 + $i),
                fingerprint: $fingerprint,
                ip: '127.0.0.1',
            );
        }

        $this->assertSame(
            6,
            VerificationChallenge::query()
                ->where('fingerprint', $fingerprint)
                ->whereNotNull('sms_sent_at')
                ->count()
        );
    }
    public function test_blocks_sms_after_thirty_successful_sends_from_the_same_ip(): void
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->times(30);
        });

        $service = app(VerificationChallengeService::class);

        $ip = '192.168.1.100';

        for ($i = 0; $i < 30; $i++) {
            $service->issue(
                purpose: VerificationPurpose::Registration,
                firstNameFa: 'علی',
                lastNameFa: 'احمدی',
                nationalityType: NationalityType::Iranian,
                identity: '001234' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                mobile: '0912000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                fingerprint: 'fingerprint-' . $i,
                ip: $ip,
            );
        }

        $this->expectException(SmsRateLimitException::class);

        $service->issue(
            purpose: VerificationPurpose::Registration,
            firstNameFa: 'علی',
            lastNameFa: 'احمدی',
            nationalityType: NationalityType::Iranian,
            identity: '0099999999',
            mobile: '09129999999',
            fingerprint: 'fingerprint-31',
            ip: $ip,
        );
    }
    public function test_does_not_count_failed_sms_toward_ip_rate_limit(): void
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->times(31)
                ->andReturnUsing(function () {
                    static $attempt = 0;

                    $attempt++;

                    if ($attempt === 1) {
                        throw new \RuntimeException('SMS failed');
                    }
                });
        });

        $service = app(VerificationChallengeService::class);

        $ip = '192.168.1.200';

        try {
            $service->issue(
                purpose: VerificationPurpose::Registration,
                firstNameFa: 'علی',
                lastNameFa: 'احمدی',
                nationalityType: NationalityType::Iranian,
                identity: '0012345678',
                mobile: '09120000001',
                fingerprint: 'fingerprint-failed',
                ip: $ip,
            );
        } catch (SmsDeliveryException $e) {
            // Expected: failed SMS must not count toward the IP rate limit.
        }

        for ($i = 0; $i < 30; $i++) {
            $service->issue(
                purpose: VerificationPurpose::Registration,
                firstNameFa: 'علی',
                lastNameFa: 'احمدی',
                nationalityType: NationalityType::Iranian,
                identity: '001234' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                mobile: '0912001' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                fingerprint: 'fingerprint-' . $i,
                ip: $ip,
            );
        }

        $this->assertSame(
            30,
            VerificationChallenge::query()
                ->where('ip', $ip)
                ->whereNotNull('sms_sent_at')
                ->count()
        );
    }
}

