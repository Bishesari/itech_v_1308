<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Membership;
use App\Models\Person;
use App\Models\Role;
use App\Models\RoleAssignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $person1 = Person::where('identity', '2063531218')->firstOrFail();
        $person2 = Person::where('identity', '3500984886')->firstOrFail();

        $centralBranch = Branch::where('code', 'BR00001')->firstOrFail();
        $westBranch = Branch::where('code', 'BR00002')->firstOrFail();

        $instructorRole = Role::where(
            'code',
            RoleCode::Instructor->value
        )->firstOrFail();

        $founderRole = Role::where(
            'code',
            RoleCode::Founder->value
        )->firstOrFail();

        $studentRole = Role::where(
            'code',
            RoleCode::Student->value
        )->firstOrFail();

        $person1CentralMembership = Membership::where('person_id', $person1->id)
            ->where('branch_id', $centralBranch->id)
            ->firstOrFail();

        $person2CentralMembership = Membership::where('person_id', $person2->id)
            ->where('branch_id', $centralBranch->id)
            ->firstOrFail();

        $person2WestMembership = Membership::where('person_id', $person2->id)
            ->where('branch_id', $westBranch->id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Scenario 1
        | Person 1 → Instructor @ Central
        |--------------------------------------------------------------------------
        */

        RoleAssignment::updateOrCreate(
            [
                'person_id' => $person1->id,
                'role_id' => $instructorRole->id,
                'membership_id' => $person1CentralMembership->id,
            ],
            [
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Scenario 2
        | Person 2 → Founder
        |          → Instructor @ Central
        |          → Student @ West
        |--------------------------------------------------------------------------
        */

        RoleAssignment::updateOrCreate(
            [
                'person_id' => $person2->id,
                'role_id' => $founderRole->id,
                'membership_id' => null,
            ],
            [
                'is_active' => true,
            ]
        );

        RoleAssignment::updateOrCreate(
            [
                'person_id' => $person2->id,
                'role_id' => $instructorRole->id,
                'membership_id' => $person2CentralMembership->id,
            ],
            [
                'is_active' => true,
            ]
        );

        RoleAssignment::updateOrCreate(
            [
                'person_id' => $person2->id,
                'role_id' => $studentRole->id,
                'membership_id' => $person2WestMembership->id,
            ],
            [
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Scenario 3
        | Person 3 → No RoleAssignment → newcomer
        |--------------------------------------------------------------------------
        |
        | عمداً هیچ رکوردی برای Person 3 ایجاد نمی‌کنیم.
        |
        */
    }
}
