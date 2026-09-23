<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            ['person_id' => 1, 'username' => 'Yasser', 'password' => Hash::make('123')],
            ['person_id' => 2, 'username' => 'Neda', 'password' => Hash::make('123')],
        ]);
    }
}
