<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProvinceSeeder::class,
            CitySeeder::class,
            BranchSeeder::class,
            PersonSeeder::class,
            UserSeeder::class,
            RoleSeeder::class,
            MembershipSeeder::class,
            RoleAssignmentSeeder::class,
        ]);
    }
}
