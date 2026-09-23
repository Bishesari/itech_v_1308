<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Membership;
use App\Models\Person;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
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

        Membership::updateOrCreate(
            [
                'person_id' => $person1->id,
                'branch_id' => $centralBranch->id,
            ],
            [
                'is_active' => true,
            ]
        );

        Membership::updateOrCreate(
            [
                'person_id' => $person2->id,
                'branch_id' => $centralBranch->id,
            ],
            [
                'is_active' => true,
            ]
        );

        Membership::updateOrCreate(
            [
                'person_id' => $person2->id,
                'branch_id' => $westBranch->id,
            ],
            [
                'is_active' => true,
            ]
        );
    }
}
