<?php

namespace Database\Seeders;

use App\Models\Domains\Sports\SportsMembership;
use Illuminate\Database\Seeder;

class SportsMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $memberships = [
            ['tier' => 'bronze', 'monthly_fee' => 500],
            ['tier' => 'silver', 'monthly_fee' => 1200],
            ['tier' => 'gold', 'monthly_fee' => 2500],
        ];

        foreach ($memberships as $membership) {
            SportsMembership::factory()->create($membership);
        }
    }
}
