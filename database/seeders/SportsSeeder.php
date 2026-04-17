<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Sports\Models\SportsCompetition;
use App\Domains\Sports\Models\SportsTraining;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SportsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Sports vertical...');

            for ($i = 1; $i <= 15; $i++) {
                SportsCompetition::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Competition {$i}",
                    'description' => "Description for competition {$i}",
                    'sport_type' => ['football', 'basketball', 'tennis'][rand(0, 2)],
                    'start_date' => now()->addDays(rand(1, 30)),
                    'end_date' => now()->addDays(rand(31, 60)),
                    'location' => "Stadium {$i}",
                    'status' => 'upcoming',
                ]);

                SportsTraining::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Training {$i}",
                    'description' => "Description for training {$i}",
                    'sport_type' => ['football', 'basketball', 'tennis'][rand(0, 2)],
                    'duration_hours' => rand(1, 3),
                    'price' => rand(1000, 10000),
                    'status' => 'active',
                ]);
            }

            $this->command->info('Sports vertical seeded successfully.');
        });
    }
}
