<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'tehran' => [
                ['name' => 'تهران', 'slug' => 'tehran'],
                ['name' => 'ری', 'slug' => 'rey'],
                ['name' => 'شمیرانات', 'slug' => 'shemiranat'],
            ],

            'isfahan' => [
                ['name' => 'اصفهان', 'slug' => 'isfahan'],
                ['name' => 'کاشان', 'slug' => 'kashan'],
                ['name' => 'خمینی شهر', 'slug' => 'khomeyni-shahr'],
            ],

            'fars' => [
                ['name' => 'شیراز', 'slug' => 'shiraz'],
                ['name' => 'مرودشت', 'slug' => 'marvdasht'],
            ],

            'gilan' => [
                ['name' => 'رشت', 'slug' => 'rasht'],
                ['name' => 'لاهیجان', 'slug' => 'lahijan'],
            ],
            'bushehr' => [
                ['name' => 'بوشهر', 'slug' => 'bushehr'],
                ['name' => 'برازجان', 'slug' => 'borazjan'],
                ['name' => 'دشتستان', 'slug' => 'dashtestan'],
                ['name' => 'کنگان', 'slug' => 'kangaan'],
                ['name' => 'دیر', 'slug' => 'dayyer'],
                ['name' => 'گناوه', 'slug' => 'genaveh'],
                ['name' => 'دیلم', 'slug' => 'deylam'],
                ['name' => 'جم', 'slug' => 'jam'],
                ['name' => 'عسلویه', 'slug' => 'asaluyeh'],
                ['name' => 'دشتی', 'slug' => 'dashti'],
                ['name' => 'تنگستان', 'slug' => 'tangestan'],
                ['name' => 'اهرم', 'slug' => 'ahram'],
                ['name' => 'خورموج', 'slug' => 'khormoj'],
            ],
        ];
        foreach ($data as $provinceSlug => $cities) {

            $province = Province::where('slug', $provinceSlug)->first();

            if (! $province) {
                continue;
            }
            foreach ($cities as $city) {
                City::updateOrCreate(
                    [
                        'province_id' => $province->id,
                        'slug' => $city['slug'],
                    ],
                    [
                        'name' => $city['name'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
