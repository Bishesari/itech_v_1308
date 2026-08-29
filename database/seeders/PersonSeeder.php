<?php

namespace Database\Seeders;

use App\Enums\NationalityType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('people')->insert([
            [
                'nationality_type' => NationalityType::Iranian->value,
                'identity' => '2063531218',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nationality_type' => NationalityType::Foreign->value,
                'identity' => '3500984886',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
