<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Consulting\Models\ConsultingProject;
use App\Domains\Consulting\Models\ConsultingSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ConsultingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Consulting vertical...');

            for ($i = 1; $i <= 20; $i++) {
                ConsultingProject::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Project {$i}",
                    'description' => "Description for project {$i}",
                    'client_id' => rand(1, 10),
                    'consultant_id' => rand(1, 5),
                    'budget' => rand(50000, 500000),
                    'status' => ['active', 'completed', 'on_hold'][rand(0, 2)],
                ]);

                ConsultingSession::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'project_id' => $i,
                    'title' => "Session {$i}",
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'duration_hours' => rand(1, 4),
                    'price' => rand(5000, 50000),
                    'status' => 'scheduled',
                ]);
            }

            $this->command->info('Consulting vertical seeded successfully.');
        });
    }
}
