<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provinces = [
            ['name' => 'آذربایجان شرقی', 'slug' => 'east-azerbaijan'],
            ['name' => 'آذربایجان غربی', 'slug' => 'west-azerbaijan'],
            ['name' => 'اردبیل', 'slug' => 'ardabil'],
            ['name' => 'اصفهان', 'slug' => 'isfahan'],
            ['name' => 'البرز', 'slug' => 'alborz'],
            ['name' => 'ایلام', 'slug' => 'ilam'],
            ['name' => 'بوشهر', 'slug' => 'bushehr'],
            ['name' => 'تهران', 'slug' => 'tehran'],
            ['name' => 'چهارمحال و بختیاری', 'slug' => 'chaharmahal-bakhtiari'],
            ['name' => 'خراسان جنوبی', 'slug' => 'south-khorasan'],
            ['name' => 'خراسان رضوی', 'slug' => 'razavi-khorasan'],
            ['name' => 'خراسان شمالی', 'slug' => 'north-khorasan'],
            ['name' => 'خوزستان', 'slug' => 'khuzestan'],
            ['name' => 'زنجان', 'slug' => 'zanjan'],
            ['name' => 'سمنان', 'slug' => 'semnan'],
            ['name' => 'سیستان و بلوچستان', 'slug' => 'sistan-baluchestan'],
            ['name' => 'فارس', 'slug' => 'fars'],
            ['name' => 'قزوین', 'slug' => 'qazvin'],
            ['name' => 'قم', 'slug' => 'qom'],
            ['name' => 'کردستان', 'slug' => 'kurdistan'],
            ['name' => 'کرمان', 'slug' => 'kerman'],
            ['name' => 'کرمانشاه', 'slug' => 'kermanshah'],
            ['name' => 'کهگیلویه و بویراحمد', 'slug' => 'kohgiluyeh-boyerahmad'],
            ['name' => 'گلستان', 'slug' => 'golestan'],
            ['name' => 'گیلان', 'slug' => 'gilan'],
            ['name' => 'لرستان', 'slug' => 'lorestan'],
            ['name' => 'مازندران', 'slug' => 'mazandaran'],
            ['name' => 'مرکزی', 'slug' => 'markazi'],
            ['name' => 'هرمزگان', 'slug' => 'hormozgan'],
            ['name' => 'همدان', 'slug' => 'hamedan'],
            ['name' => 'یزد', 'slug' => 'yazd'],
        ];

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                ['slug' => $province['slug']],
                $province
            );
        }
    }
}
