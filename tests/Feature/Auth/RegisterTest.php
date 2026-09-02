<?php

namespace Tests\Feature\Auth;

use App\Contracts\SmsGateway;
use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Models\VerificationChallenge;
use App\Services\Verification\VerificationChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_otp_verifies_challenge_when_code_is_correct(): void
    {
        $challenge = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->call('verifyOtp');

        $this->assertNotNull(
            $challenge->fresh()->verified_at
        );
    }

    public function test_verify_otp_adds_error_when_code_is_invalid(): void
    {
        $challenge = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '654321')
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $this->assertNull(
            $challenge->fresh()->verified_at
        );

        $this->assertSame(
            1,
            $challenge->fresh()->attempts
        );
    }

    public function test_verify_otp_adds_error_when_challenge_is_expired(): void
    {
        $challenge = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $this->assertNull(
            $challenge->fresh()->verified_at
        );

        $this->assertSame(
            0,
            $challenge->fresh()->attempts
        );
    }

    public function test_verify_otp_adds_error_when_attempts_are_exceeded(): void
    {
        $challenge = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 5,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $this->assertNull(
            $challenge->fresh()->verified_at
        );

        $this->assertSame(
            5,
            $challenge->fresh()->attempts
        );
    }

    public function test_verify_otp_adds_error_when_challenge_does_not_exist(): void
    {
        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);
    }

    public function test_verify_otp_validates_otp_before_verification(): void
    {
        $service = Mockery::mock(VerificationChallengeService::class);

        $service->shouldNotReceive('verify');

        $this->app->instance(
            VerificationChallengeService::class,
            $service
        );

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '12345')
            ->call('verifyOtp')
            ->assertHasErrors([
                'otp' => ['digits'],
            ]);
    }

    public function test_verify_otp_requires_otp(): void
    {
        $service = Mockery::mock(VerificationChallengeService::class);

        $service->shouldNotReceive('verify');

        $this->app->instance(
            VerificationChallengeService::class,
            $service
        );

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '')
            ->call('verifyOtp')
            ->assertHasErrors([
                'otp' => ['required'],
            ]);
    }

    public function test_verify_otp_rejects_otp_with_more_than_six_digits(): void
    {
        $service = Mockery::mock(VerificationChallengeService::class);

        $service->shouldNotReceive('verify');

        $this->app->instance(
            VerificationChallengeService::class,
            $service
        );

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '1234567')
            ->call('verifyOtp')
            ->assertHasErrors([
                'otp' => ['digits'],
            ]);
    }

    public function test_verify_otp_normalizes_persian_digits_before_verification(): void
    {
        $challenge = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '۱۲۳۴۵۶')
            ->call('verifyOtp');

        $this->assertNotNull(
            $challenge->fresh()->verified_at
        );
    }

    public function test_verify_otp_records_fifth_invalid_attempt(): void
    {
        $challenge = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 4,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '654321')
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $challenge = $challenge->fresh();

        $this->assertSame(5, $challenge->attempts);
        $this->assertNull($challenge->verified_at);
    }

    public function test_verify_otp_adds_error_when_challenge_is_already_verified(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 0,
            'verified_at' => now(),
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);
    }

    public function test_verify_otp_clears_previous_otp_error_after_success(): void
    {
        $challenge = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '654321')
            ->call('verifyOtp')
            ->assertHasErrors(['otp'])
            ->set('otp', '123456')
            ->call('verifyOtp')
            ->assertHasNoErrors('otp');

        $this->assertNotNull(
            $challenge->fresh()->verified_at
        );
    }

    public function test_continue_register_creates_challenge_and_sets_otp_expiration(): void
    {
        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        Livewire::test('pages::auth.register')
            ->set('first_name_fa', 'علی')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '2063531218')
            ->set('mobile', '09123456789')
            ->call('continueRegister');

        $challenge = VerificationChallenge::query()
            ->where('purpose', VerificationPurpose::Registration)
            ->where('mobile', '09123456789')
            ->latest('id')
            ->first();

        $this->assertNotNull($challenge);
        $this->assertNotNull($challenge->sms_sent_at);
        $this->assertNotNull($challenge->expires_at);
    }

    public function test_continue_register_adds_error_when_sms_delivery_fails(): void
    {
        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'))
            ->andThrow(new RuntimeException('SMS gateway failed'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('first_name_fa', 'علی')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '2063531218')
            ->set('mobile', '09123456789')
            ->call('continueRegister');

        $component->assertHasErrors(['verification']);

        $this->assertDatabaseHas('verification_challenges', [
            'purpose' => VerificationPurpose::Registration->value,
            'mobile' => '09123456789',
            'sms_sent_at' => null,
        ]);
    }

    public function test_continue_register_does_not_send_sms_when_active_challenge_exists(): void
    {
        $existingChallenge = VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'verified_at' => null,
            'sms_sent_at' => now(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);
        $smsGateway->shouldNotReceive('sendOtp');

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('first_name_fa', 'علی')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '2063531218')
            ->set('mobile', '09123456789')
            ->call('continueRegister');

        $component->assertHasNoErrors();

        $this->assertSame(
            $existingChallenge->id,
            VerificationChallenge::query()
                ->where('purpose', VerificationPurpose::Registration)
                ->where('mobile', '09123456789')
                ->latest('id')
                ->value('id')
        );
    }

    public function test_continue_register_creates_new_challenge_when_previous_challenge_is_expired(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'verified_at' => null,
            'sms_sent_at' => now()->subMinute(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('first_name_fa', 'علی')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '2063531218')
            ->set('mobile', '09123456789')
            ->call('continueRegister');

        $component->assertHasNoErrors();

        $this->assertSame(
            2,
            VerificationChallenge::query()
                ->where('purpose', VerificationPurpose::Registration)
                ->where('mobile', '09123456789')
                ->count()
        );
    }

    public function test_resend_otp_sends_new_code_and_updates_expiration(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'verified_at' => null,
            'sms_sent_at' => now()->subMinute(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->call('resendOtp');

        $component->assertHasNoErrors();

        $newChallenge = VerificationChallenge::query()
            ->where('purpose', VerificationPurpose::Registration)
            ->where('mobile', '09123456789')
            ->latest('id')
            ->first();

        $this->assertNotNull($newChallenge);
        $this->assertNotSame(
            '123456',
            $newChallenge->verification_code
        );
        $this->assertNotNull($newChallenge->sms_sent_at);
        $this->assertNotNull($newChallenge->expires_at);
    }

    public function test_resend_otp_adds_error_when_active_challenge_exists(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinute(),
            'verified_at' => null,
            'sms_sent_at' => now(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);
        $smsGateway->shouldNotReceive('sendOtp');

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->call('resendOtp');

        $component->assertHasErrors(['otp']);
    }

    public function test_resend_otp_adds_error_when_sms_delivery_fails(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'verified_at' => null,
            'sms_sent_at' => now()->subMinute(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'))
            ->andThrow(new RuntimeException('SMS gateway failed'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->call('resendOtp');

        $component->assertHasErrors(['otp']);

        $this->assertDatabaseHas('verification_challenges', [
            'purpose' => VerificationPurpose::Registration->value,
            'mobile' => '09123456789',
            'sms_sent_at' => null,
        ]);
    }

    public function test_resend_otp_adds_error_when_challenge_does_not_exist(): void
    {
        $smsGateway = Mockery::mock(SmsGateway::class);
        $smsGateway->shouldNotReceive('sendOtp');

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->call('resendOtp');

        $component->assertHasErrors(['otp']);
    }

    public function test_continue_register_validates_form_before_issuing_challenge(): void
    {
        $service = Mockery::mock(VerificationChallengeService::class);
        $service->shouldNotReceive('issue');

        $this->app->instance(VerificationChallengeService::class, $service);

        Livewire::test('pages::auth.register')
            ->set('first_name_fa', '')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '2063531218')
            ->set('mobile', '09123456789')
            ->call('continueRegister')
            ->assertHasErrors(['first_name_fa']);
    }

    public function test_continue_register_normalizes_persian_digits_before_issuing_challenge(): void
    {
        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('first_name_fa', 'علی')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '۲۰۶۳۵۳۱۲۱۸')
            ->set('mobile', '۰۹۱۲۳۴۵۶۷۸۹')
            ->call('continueRegister');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('verification_challenges', [
            'purpose' => VerificationPurpose::Registration->value,
            'identity' => '2063531218',
            'mobile' => '09123456789',
        ]);
    }

    public function test_continue_register_sets_otp_expires_at_from_challenge(): void
    {
        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('first_name_fa', 'علی')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '2063531218')
            ->set('mobile', '09123456789')
            ->call('continueRegister');

        $challenge = VerificationChallenge::query()
            ->where('purpose', VerificationPurpose::Registration)
            ->where('mobile', '09123456789')
            ->latest('id')
            ->first();

        $this->assertNotNull($challenge);

        $component->assertSet(
            'otp_expires_at',
            $challenge->expires_at->toISOString()
        );
    }

    public function test_resend_otp_clears_previous_otp_after_success(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'verified_at' => null,
            'sms_sent_at' => now()->subMinute(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->call('resendOtp')
            ->assertSet('otp', '');
    }

    public function test_resend_otp_keeps_previous_otp_when_sms_delivery_fails(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'verified_at' => null,
            'sms_sent_at' => now()->subMinute(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'))
            ->andThrow(new RuntimeException('SMS gateway failed'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->call('resendOtp')
            ->assertHasErrors(['otp'])
            ->assertSet('otp', '123456');
    }

    public function test_verify_otp_clears_otp_after_success(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->call('verifyOtp')
            ->assertHasNoErrors()
            ->assertSet('otp', '');
    }

    public function test_verify_otp_clears_otp_expiration_after_success(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->addMinutes(2),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp', '123456')
            ->set('otp_expires_at', now()->addMinutes(2)->toISOString())
            ->call('verifyOtp')
            ->assertHasNoErrors()
            ->assertSet('otp_expires_at', null);
    }

    public function test_resend_otp_keeps_previous_expiration_when_sms_delivery_fails(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'verified_at' => null,
            'sms_sent_at' => now()->subMinute(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'))
            ->andThrow(new RuntimeException('SMS gateway failed'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $oldExpiration = now()->addSeconds(30)->toISOString();

        Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp_expires_at', $oldExpiration)
            ->call('resendOtp')
            ->assertHasErrors(['otp'])
            ->assertSet('otp_expires_at', $oldExpiration);
    }

    public function test_resend_otp_updates_otp_expires_at_after_success(): void
    {
        VerificationChallenge::factory()->create([
            'purpose' => VerificationPurpose::Registration,
            'mobile' => '09123456789',
            'verification_code' => '123456',
            'expires_at' => now()->subMinute(),
            'verified_at' => null,
            'sms_sent_at' => now()->subMinute(),
        ]);

        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $oldExpiration = now()->subMinute()->toISOString();

        $component = Livewire::test('pages::auth.register')
            ->set('mobile', '09123456789')
            ->set('otp_expires_at', $oldExpiration)
            ->call('resendOtp');

        $newChallenge = VerificationChallenge::query()
            ->where('purpose', VerificationPurpose::Registration)
            ->where('mobile', '09123456789')
            ->latest('id')
            ->first();

        $this->assertNotNull($newChallenge);

        $component->assertSet(
            'otp_expires_at',
            $newChallenge->expires_at->toISOString()
        );
    }

    public function test_continue_register_does_not_set_otp_expiration_when_sms_delivery_fails(): void
    {
        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'))
            ->andThrow(new RuntimeException('SMS gateway failed'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('first_name_fa', 'علی')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '2063531218')
            ->set('mobile', '09123456789')
            ->call('continueRegister');

        $component
            ->assertHasErrors(['verification'])
            ->assertSet('otp_expires_at', null);
    }

    public function test_continue_register_clears_previous_verification_error_after_success(): void
    {
        $smsGateway = Mockery::mock(SmsGateway::class);

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', Mockery::type('string'))
            ->andThrow(new RuntimeException('SMS gateway failed'));

        $smsGateway
            ->shouldReceive('sendOtp')
            ->once()
            ->with('09123456780', Mockery::type('string'));

        $this->app->instance(SmsGateway::class, $smsGateway);

        $component = Livewire::test('pages::auth.register')
            ->set('first_name_fa', 'علی')
            ->set('last_name_fa', 'رضایی')
            ->set('nationality_type', NationalityType::Iranian->value)
            ->set('identity', '2063531218')
            ->set('mobile', '09123456789')
            ->call('continueRegister');

        $component->assertHasErrors(['verification']);

        $component
            ->set('mobile', '09123456780')
            ->call('continueRegister');

        $component->assertHasNoErrors(['verification']);
    }
}
