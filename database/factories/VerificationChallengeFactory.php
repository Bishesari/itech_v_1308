<?php

namespace Database\Factories;

use App\Enums\NationalityType;
use App\Enums\VerificationPurpose;
use App\Models\VerificationChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationChallenge>
 */
class VerificationChallengeFactory extends Factory
{
    protected $model = VerificationChallenge::class;

    public function definition(): array
    {
        return [
            'first_name_fa' => 'علی',
            'last_name_fa' => 'احمدی',

            'nationality_type' => NationalityType::Iranian,

            'identity' => fake()->numerify('##########'),

            'mobile' => fake()->numerify('09#########'),

            'purpose' => VerificationPurpose::Registration,

            'verification_code' => fake()->numerify('######'),

            'fingerprint' => null,

            'ip' => '127.0.0.1',

            'attempts' => 0,

            'expires_at' => now()->addMinutes(2),

            'sms_sent_at' => now(),

            'verified_at' => null,
        ];
    }
}
