<?php

namespace Database\Factories;

use App\Enums\NationalityType;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nationality_type' => NationalityType::Iranian,
            'identity' => fake()->unique()->numerify('##########'),
            'gender' => fake()->boolean(),
            'first_name_fa' => fake()->firstName(),
            'last_name_fa' => fake()->lastName(),
            'nickname' => null,
        ];
    }
}
