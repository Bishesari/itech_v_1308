<?php

namespace App\Services\Registration;

use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Models\Mobile;
use App\Models\Person;
use App\Models\User;
use App\Services\Verification\VerificationChallengeService;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function __construct(
        private readonly VerificationChallengeService $verificationChallengeService,
    ) {}

    public function complete(
        VerificationPurpose $purpose,
        string $mobile,
        string $verificationCode,
    ): User {
        return DB::transaction(function () use (
            $purpose,
            $mobile,
            $verificationCode,
        ) {
            $challenge = $this->verificationChallengeService->verify(
                purpose: $purpose,
                mobile: $mobile,
                verificationCode: $verificationCode,
            );

            $person = Person::create([
                'nationality_type' => $challenge->nationality_type,
                'identity' => $challenge->identity,
                'first_name_fa' => $challenge->first_name_fa,
                'last_name_fa' => $challenge->last_name_fa,
            ]);

            $mobileModel = Mobile::query()->firstOrCreate([
                'mobile' => $challenge->mobile,
            ]);




            $person->mobiles()->attach($mobileModel);
            $user = User::create([
                'person_id' => $person->id,
                'username' => $challenge->identity,
                'password' => $challenge->identity,
            ]);

            $challenge->update([
                'verified_at' => now(),
            ]);
            return $user;
        });
    }
}

