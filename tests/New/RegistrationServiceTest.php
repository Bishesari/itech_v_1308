<?php

use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Models\Mobile;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Models\Person;
use App\Services\Registration\RegistrationService;

it('creates a person after successful otp verification', function () {
    $challenge = VerificationChallenge::factory()->create([
        'purpose' => VerificationPurpose::Registration,
        'first_name_fa' => 'علی',
        'last_name_fa' => 'احمدی',
        'nationality_type' => NationalityType::Iranian,
        'identity' => '2063531218',
        'mobile' => '09123456789',
        'verification_code' => '123456',
        'expires_at' => now()->addMinutes(2),
        'sms_sent_at' => now(),
    ]);

    app(RegistrationService::class)->complete(
        purpose: VerificationPurpose::Registration,
        mobile: '09123456789',
        verificationCode: '123456',
    );

    $person = Person::query()
        ->where('identity', '2063531218')
        ->first();

    expect($person)->not->toBeNull()
        ->and($person->first_name_fa)->toBe('علی')
        ->and($person->last_name_fa)->toBe('احمدی')
        ->and($person->nationality_type)->toBe(NationalityType::Iranian);

    $mobile = Mobile::query()
        ->where('mobile', '09123456789')
        ->first();

    expect($mobile)->not->toBeNull()
        ->and($person->mobiles()->whereKey($mobile->id)->exists())
        ->toBeTrue()
        ->and($person->mobiles()->whereKey($mobile->id)->first()->pivot->created_at)
        ->not->toBeNull()
        ->and($person->mobiles()->whereKey($mobile->id)->first()->pivot->updated_at)
        ->not->toBeNull();

    $user = User::query()
        ->where('person_id', $person->id)
        ->first();

    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('2063531218')
        ->and(
            \Illuminate\Support\Facades\Hash::check(
                '2063531218',
                $user->password,
            )
        )->toBeTrue()
        ->and($challenge->fresh()->verified_at)->not->toBeNull();

});
it('rolls back registration when user creation fails', function () {
    $existingPerson = Person::factory()->create([
        'identity' => '1111111111',
    ]);

    User::create([
        'person_id' => $existingPerson->id,
        'username' => '2063531218',
        'password' => '1111111111',
    ]);

    $challenge = VerificationChallenge::factory()->create([
        'purpose' => VerificationPurpose::Registration,
        'first_name_fa' => 'علی',
        'last_name_fa' => 'احمدی',
        'nationality_type' => NationalityType::Iranian,
        'identity' => '2063531218',
        'mobile' => '09123456789',
        'verification_code' => '123456',
        'expires_at' => now()->addMinutes(2),
        'sms_sent_at' => now(),
    ]);

    expect(fn() => app(RegistrationService::class)->complete(
        purpose: VerificationPurpose::Registration,
        mobile: '09123456789',
        verificationCode: '123456',
    ))->toThrow(\Illuminate\Database\QueryException::class)
        ->and(
            Person::query()
                ->where('identity', '2063531218')
                ->exists()
        )->toBeFalse()
        ->and(
            Mobile::query()
                ->where('mobile', '09123456789')
                ->exists()
        )->toBeFalse()
        ->and($challenge->fresh()->verified_at)->toBeNull();

});
it('uses registration data from the verified challenge', function () {
    $challenge = VerificationChallenge::factory()->create([
        'purpose' => VerificationPurpose::Registration,
        'first_name_fa' => 'علی',
        'last_name_fa' => 'احمدی',
        'nationality_type' => NationalityType::Iranian,
        'identity' => '2063531218',
        'mobile' => '09123456789',
        'verification_code' => '123456',
        'expires_at' => now()->addMinutes(2),
        'sms_sent_at' => now(),
    ]);

    app(RegistrationService::class)->complete(
        purpose: VerificationPurpose::Registration,

        // Deliberately different data from the challenge.
        mobile: '09123456789',
        verificationCode: '123456',
    );

    expect(Person::query()->where('identity', '2063531218')->exists())
        ->toBeTrue()
        ->and(Person::query()->where('identity', '1111111111')->exists())
        ->toBeFalse();

});
