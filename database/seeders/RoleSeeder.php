<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Enums\RoleScope;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'code'        => RoleCode::SiteAdmin->value,
                'name'        => 'مدیر سامانه',
                'scope'       => RoleScope::System->value,
                'description' => 'مدیر فنی سامانه با دسترسی کامل به نرم‌افزار.',
                'color' => 'red',
            ],
            [
                'code'        => RoleCode::Founder->value,
                'name'        => 'مؤسس',
                'scope'       => RoleScope::Institute->value,
                'description' => 'مالک و مدیر کل آموزشگاه که می‌تواند در کنار آن نقش‌های عملیاتی نیز داشته باشد.',
                'color' => 'teal',
            ],
            [
                'code'        => RoleCode::Administrative->value,
                'name'        => 'مسئول اداری',
                'scope'       => RoleScope::Branch->value,
                'description' => 'مسئول امور اجرایی و اداری یک شعبه.',
                'color' => 'fuchsia',
            ],
            [
                'code'        => RoleCode::Instructor->value,
                'name'        => 'مربی',
                'scope'       => RoleScope::Branch->value,
                'description' => 'مدرس یا مربی فعال در یک یا چند شعبه.',
                'color' => 'amber',
            ],
            [
                'code'        => RoleCode::Student->value,
                'name'        => 'هنرجو',
                'scope'       => RoleScope::Branch->value,
                'description' => 'فردی که در یک یا چند دوره آموزشی شرکت می‌کند.',
                'color' => 'lime',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['code' => $role['code']],
                [
                    'name'        => $role['name'],
                    'scope'       => $role['scope'],
                    'description' => $role['description'],
                    'color' => $role['color'],
                    'is_active'   => true,
                ]
            );
        }
    }
}
