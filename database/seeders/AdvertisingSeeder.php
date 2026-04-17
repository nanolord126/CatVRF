<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Advertising\Models\AdvertisingCampaign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AdvertisingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Advertising vertical...');

            for ($i = 1; $i <= 20; $i++) {
                AdvertisingCampaign::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Campaign {$i}",
                    'description' => "Description for campaign {$i}",
                    'type' => ['display', 'search', 'social'][rand(0, 2)],
                    'budget' => rand(10000, 500000),
                    'start_date' => now()->subDays(rand(1, 30)),
                    'end_date' => now()->addDays(rand(1, 90)),
                    'status' => 'active',
                ]);
            }

            $this->command->info('Advertising vertical seeded successfully.');
        });
    }
}
