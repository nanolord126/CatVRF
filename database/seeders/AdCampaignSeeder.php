<?php

namespace Database\Seeders;

use App\Models\Domains\Advertising\AdCampaign;
use Illuminate\Database\Seeder;

class AdCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $campaigns = [
            ['title' => 'Summer Sale', 'budget' => 5000, 'spent' => 2500],
            ['title' => 'Brand Awareness', 'budget' => 10000, 'spent' => 0],
            ['title' => 'Flash Deal', 'budget' => 2000, 'spent' => 1800],
        ];

        foreach ($campaigns as $campaign) {
            AdCampaign::factory()->create($campaign);
        }
    }
}
