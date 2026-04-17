<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Content\Models\ContentCampaign;
use App\Domains\Content\Models\ContentMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Content vertical...');

            for ($i = 1; $i <= 20; $i++) {
                ContentCampaign::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Campaign {$i}",
                    'description' => "Description for campaign {$i}",
                    'type' => ['social', 'email', 'display'][rand(0, 2)],
                    'budget' => rand(10000, 100000),
                    'start_date' => now()->subDays(rand(1, 30)),
                    'end_date' => now()->addDays(rand(1, 30)),
                    'status' => 'active',
                ]);

                ContentMedia::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'campaign_id' => $i,
                    'title' => "Media {$i}",
                    'type' => ['image', 'video', 'text'][rand(0, 2)],
                    'url' => "https://example.com/media/{$i}",
                    'status' => 'published',
                ]);
            }

            $this->command->info('Content vertical seeded successfully.');
        });
    }
}
