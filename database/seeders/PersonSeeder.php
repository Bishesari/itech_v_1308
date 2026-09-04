<?php

namespace Database\Seeders;

use App\Enums\NationalityType;
use App\Models\Person;
use Illuminate\Database\Seeder;

class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Person::create([
            'nationality_type' => NationalityType::Iranian->value,
            'identity' => '2063531218',
            'first_name_fa' => 'یاسر',
            'last_name_fa' => 'بیشه سری',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Person::create([
            'nationality_type' => NationalityType::Iranian->value,
            'identity' => '3500984886',
            'first_name_fa' => 'ندا',
            'last_name_fa' => 'بخشی زاده',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Person::create([
            'nationality_type' => NationalityType::Iranian->value,
            'identity' => '1020304050',
            'first_name_fa' => 'رز',
            'last_name_fa' => 'بیشه سری',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
