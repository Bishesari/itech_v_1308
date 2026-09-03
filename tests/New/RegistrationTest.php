<?php

use App\Enums\NationalityType;
use App\Models\Person;
use Livewire\Livewire;
use App\Models\VerificationChallenge;
use App\Enums\VerificationPurpose;

it('rejects registration when identity already exists', function () {
    $identity = '2063531218';

    Person::factory()->create([
        'identity' => $identity,
        'nationality_type' => NationalityType::Iranian,
    ]);

    Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', $identity)
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasErrors([
            'identity',
        ]);
});

it('issues verification challenge for a new identity', function () {
    Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors()
        ->assertSet('otp', '')
        ->assertSet('otp_expires_at', fn ($value) => $value !== null);

    $challenge = VerificationChallenge::query()->latest('id')->first();

    expect($challenge)->not->toBeNull()
        ->and($challenge->identity)->toBe('2063531218')
        ->and($challenge->mobile)->toBe('09123456789')
        ->and($challenge->purpose)->toBe(VerificationPurpose::Registration)
        ->and($challenge->verification_code)->toMatch('/^\d{6}$/')
        ->and($challenge->sms_sent_at)->not->toBeNull();
});
it('completes registration after successful otp verification', function () {
    $component = Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors();

    $challenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    expect($challenge)->not->toBeNull();

    $component
        ->set('otp', $challenge->verification_code)
        ->call('verifyOtp')
        ->assertHasNoErrors()
        ->assertSet('otp', '')
        ->assertSet('otp_expires_at', null);


    expect(
        \App\Models\Person::query()
            ->where('identity', '2063531218')
            ->exists()
    )->toBeTrue();

    $person = \App\Models\Person::query()
        ->where('identity', '2063531218')
        ->first();

    expect(
        \App\Models\User::query()
            ->where('person_id', $person->id)
            ->exists()
    )->toBeTrue()
        ->and($challenge->fresh()->verified_at)->not->toBeNull();

});
it('logs in the user and redirects to dashboard after successful otp verification', function () {
    $component = Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors();

    $challenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    $component
        ->set('otp', $challenge->verification_code)
        ->call('verifyOtp')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(auth()->check())->toBeTrue();
});
it('rejects an invalid otp and does not complete registration', function () {
    $component = Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors();

    $challenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    expect($challenge)->not->toBeNull();

    $component
        ->set('otp', '999999')
        ->call('verifyOtp')
        ->assertHasErrors(['otp']);

    expect(
        \App\Models\Person::query()
            ->where('identity', '2063531218')
            ->exists()
    )->toBeFalse()
        ->and(
            \App\Models\User::query()
                ->where('username', '2063531218')
                ->exists()
        )->toBeFalse()
        ->and($challenge->fresh()->verified_at)->toBeNull();

});
it('rejects an expired otp and does not complete registration', function () {
    $component = Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors();

    $challenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    expect($challenge)->not->toBeNull();

    $challenge->update([
        'expires_at' => now()->subMinute(),
    ]);

    $component
        ->set('otp', $challenge->verification_code)
        ->call('verifyOtp')
        ->assertHasErrors(['otp']);

    expect(
        \App\Models\Person::query()
            ->where('identity', '2063531218')
            ->exists()
    )->toBeFalse()
        ->and(
            \App\Models\User::query()
                ->where('username', '2063531218')
                ->exists()
        )->toBeFalse()
        ->and($challenge->fresh()->verified_at)->toBeNull();

});
it('rejects otp after maximum attempts are exceeded', function () {
    $component = Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors();

    $challenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    expect($challenge)->not->toBeNull();

    $maxAttempts = config('verification.otp.max_attempts');

    $challenge->update([
        'attempts' => $maxAttempts,
    ]);

    $component
        ->set('otp', $challenge->verification_code)
        ->call('verifyOtp')
        ->assertHasErrors(['otp']);

    expect(
        \App\Models\Person::query()
            ->where('identity', '2063531218')
            ->exists()
    )->toBeFalse()
        ->and(
            \App\Models\User::query()
                ->where('username', '2063531218')
                ->exists()
        )->toBeFalse()
        ->and($challenge->fresh()->verified_at)->toBeNull();

});
it('resends otp after the current challenge expires', function () {
    $component = Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors();

    $firstChallenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    expect($firstChallenge)->not->toBeNull();

    $firstChallenge->update([
        'expires_at' => now()->subMinute(),
    ]);

    $component
        ->call('resendOtp')
        ->assertHasNoErrors();

    $secondChallenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    expect($secondChallenge)->not->toBeNull()
        ->and($secondChallenge->id)->not->toBe($firstChallenge->id)
        ->and($secondChallenge->mobile)->toBe('09123456789')
        ->and($secondChallenge->purpose)->toBe(VerificationPurpose::Registration)
        ->and($secondChallenge->verification_code)->toMatch('/^\d{6}$/')
        ->and($secondChallenge->sms_sent_at)->not->toBeNull()
        ->and($secondChallenge->expires_at->isFuture())->toBeTrue()
        ->and($component->get('otp'))->toBe('')
        ->and($component->get('otp_expires_at'))->not->toBeNull();

});
it('does not resend otp while the current challenge is active', function () {
    $component = Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors();

    $firstChallenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    expect($firstChallenge)->not->toBeNull();

    $challengeCount = VerificationChallenge::query()->count();

    $component
        ->call('resendOtp')
        ->assertHasErrors(['otp']);

    expect(
        VerificationChallenge::query()->count()
    )->toBe($challengeCount)
        ->and(
            VerificationChallenge::query()
                ->latest('id')
                ->first()
                ->id
        )->toBe($firstChallenge->id);

});
it('sets otp expiration after issuing verification challenge', function () {
    Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors()
        ->assertSet('otp_expires_at', fn ($value) => $value !== null);
});
it('clears otp error after successful verification', function () {
    $component = Livewire::test('pages::auth.register')
        ->set('first_name_fa', 'علی')
        ->set('last_name_fa', 'احمدی')
        ->set('nationality_type', NationalityType::Iranian)
        ->set('identity', '2063531218')
        ->set('mobile', '09123456789')
        ->call('continueRegister')
        ->assertHasNoErrors();

    $challenge = VerificationChallenge::query()
        ->latest('id')
        ->first();

    $component
        ->set('otp', '000000')
        ->call('verifyOtp')
        ->assertHasErrors('otp');

    $component
        ->set('otp', $challenge->verification_code)
        ->call('verifyOtp')
        ->assertHasNoErrors('otp')
        ->assertRedirect(route('dashboard'));
});
