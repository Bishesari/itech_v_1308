<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tehran = Province::where('slug', 'tehran')->firstOrFail();

        $tehranCity = City::where('province_id', $tehran->id)
            ->where('slug', 'tehran')
            ->firstOrFail();

        Branch::updateOrCreate(
            ['code' => 'BR00001'],
            [
                'short_name' => 'مرکزی',
                'is_main' => true,
                'province_id' => $tehran->id,
                'city_id' => $tehranCity->id,
                'address' => 'تهران، شعبه مرکزی',
                'postal_code' => null,
                'phone' => null,
                'mobile' => null,
                'is_active' => true,
            ]
        );

        Branch::updateOrCreate(
            ['code' => 'BR00002'],
            [
                'short_name' => 'غرب',
                'is_main' => false,
                'province_id' => $tehran->id,
                'city_id' => $tehranCity->id,
                'address' => 'تهران، شعبه غرب',
                'postal_code' => null,
                'phone' => null,
                'mobile' => null,
                'is_active' => true,
            ]
        );
    }
}
