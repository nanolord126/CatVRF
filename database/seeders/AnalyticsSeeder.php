<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Analytics\Models\AnalyticsMetric;
use App\Domains\Analytics\Models\AnalyticsReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Analytics vertical...');

            for ($i = 1; $i <= 25; $i++) {
                AnalyticsMetric::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Metric {$i}",
                    'description' => "Description for metric {$i}",
                    'type' => ['revenue', 'users', 'conversion'][rand(0, 2)],
                    'value' => rand(100, 10000),
                    'unit' => ['count', 'percent', 'currency'][rand(0, 2)],
                    'recorded_at' => now()->subDays(rand(1, 30)),
                ]);

                AnalyticsReport::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Report {$i}",
                    'description' => "Description for report {$i}",
                    'type' => ['daily', 'weekly', 'monthly'][rand(0, 2)],
                    'generated_at' => now()->subDays(rand(1, 30)),
                    'status' => 'completed',
                ]);
            }

            $this->command->info('Analytics vertical seeded successfully.');
        });
    }
}
