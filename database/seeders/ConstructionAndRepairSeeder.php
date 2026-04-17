<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\ConstructionAndRepair\Models\ConstructionAndRepairProject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ConstructionAndRepairSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Construction And Repair vertical...');

            for ($i = 1; $i <= 20; $i++) {
                ConstructionAndRepairProject::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'title' => "Project {$i}",
                    'description' => "Description for project {$i}",
                    'type' => ['construction', 'renovation', 'repair'][rand(0, 2)],
                    'client_id' => rand(1, 10),
                    'budget' => rand(50000, 5000000),
                    'status' => ['planning', 'in_progress', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Construction And Repair vertical seeded successfully.');
        });
    }
}
