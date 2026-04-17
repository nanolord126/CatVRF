<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Fitness\Models\FitnessMembership;
use App\Domains\Fitness\Models\FitnessWorkout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FitnessSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Fitness vertical...');

            for ($i = 1; $i <= 20; $i++) {
                FitnessMembership::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Membership {$i}",
                    'description' => "Description for membership {$i}",
                    'price_per_month' => rand(2000, 20000),
                    'duration_months' => rand(1, 12),
                    'features' => json_encode(['gym', 'pool', 'sauna']),
                    'status' => 'active',
                ]);

                FitnessWorkout::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Workout {$i}",
                    'description' => "Description for workout {$i}",
                    'duration_minutes' => rand(30, 120),
                    'difficulty' => ['beginner', 'intermediate', 'advanced'][rand(0, 2)],
                    'calories' => rand(200, 800),
                    'status' => 'active',
                ]);
            }

            $this->command->info('Fitness vertical seeded successfully.');
        });
    }
}
