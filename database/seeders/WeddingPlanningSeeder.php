<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\WeddingPlanning\Models\WeddingPlanningWedding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WeddingPlanningSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Wedding Planning vertical...');

            for ($i = 1; $i <= 15; $i++) {
                WeddingPlanningWedding::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'couple_names' => "Couple {$i}",
                    'wedding_date' => now()->addDays(rand(30, 365)),
                    'venue' => "Venue {$i}",
                    'guests_count' => rand(50, 300),
                    'budget' => rand(200000, 2000000),
                    'status' => ['planning', 'confirmed', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Wedding Planning vertical seeded successfully.');
        });
    }
}
