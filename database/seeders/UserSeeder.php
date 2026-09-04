<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'person_id' => 1,
            'username' => 'Yasser',
            'password' => '12345678'
        ]);
    }
}
