<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Freelance\Models\FreelanceProject;
use App\Domains\Freelance\Models\FreelanceContract;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FreelanceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Freelance vertical...');

            for ($i = 1; $i <= 25; $i++) {
                FreelanceProject::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'title' => "Project {$i}",
                    'description' => "Description for project {$i}",
                    'client_id' => rand(1, 10),
                    'freelancer_id' => rand(1, 10),
                    'budget' => rand(10000, 500000),
                    'status' => ['open', 'in_progress', 'completed'][rand(0, 2)],
                ]);

                FreelanceContract::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'project_id' => $i,
                    'terms' => "Contract terms for project {$i}",
                    'amount' => rand(10000, 500000),
                    'start_date' => now()->subDays(rand(1, 30)),
                    'end_date' => now()->addDays(rand(1, 90)),
                    'status' => 'active',
                ]);
            }

            $this->command->info('Freelance vertical seeded successfully.');
        });
    }
}
