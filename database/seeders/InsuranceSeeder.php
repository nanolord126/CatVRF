<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Insurance\Models\InsurancePolicy;
use App\Domains\Insurance\Models\InsuranceClaim;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InsuranceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Insurance vertical...');

            for ($i = 1; $i <= 25; $i++) {
                InsurancePolicy::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'policy_number' => "POL-{$i}",
                    'user_id' => rand(1, 10),
                    'type' => ['health', 'car', 'property', 'life'][rand(0, 3)],
                    'coverage_amount' => rand(100000, 10000000),
                    'premium' => rand(5000, 100000),
                    'start_date' => now()->subDays(rand(1, 365)),
                    'end_date' => now()->addDays(rand(1, 365)),
                    'status' => 'active',
                ]);

                InsuranceClaim::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'policy_id' => $i,
                    'claim_number' => "CLM-{$i}",
                    'description' => "Claim description {$i}",
                    'amount' => rand(10000, 500000),
                    'status' => ['pending', 'approved', 'rejected'][rand(0, 2)],
                    'submitted_at' => now()->subDays(rand(1, 30)),
                ]);
            }

            $this->command->info('Insurance vertical seeded successfully.');
        });
    }
}
