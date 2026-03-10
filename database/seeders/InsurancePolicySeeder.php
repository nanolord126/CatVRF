<?php

namespace Database\Seeders;

use App\Models\Domains\Insurance\InsurancePolicy;
use Illuminate\Database\Seeder;

class InsurancePolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            ['policy_number' => 'POL-001', 'type' => 'health', 'premium_amount' => 200, 'coverage_amount' => 100000],
            ['policy_number' => 'POL-002', 'type' => 'auto', 'premium_amount' => 150, 'coverage_amount' => 50000],
            ['policy_number' => 'POL-003', 'type' => 'home', 'premium_amount' => 250, 'coverage_amount' => 500000],
        ];

        foreach ($policies as $policy) {
            InsurancePolicy::factory()->create($policy);
        }
    }
}
