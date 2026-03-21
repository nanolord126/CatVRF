<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ConstructionProject;
use App\Models\InsurancePolicy;
use App\Models\PromoCampaign;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Новые вертикали (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class NewVerticalsSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = ['grand-hotel', 'branch-east-2026'];

        foreach ($tenants as $tenantId) {
            tenancy()->initialize($tenantId);

            // 1. Promo Campaign: 2+1
            PromoCampaign::firstOrCreate(['name' => 'Spring 2+1 Cyber Sale'], [
                'type' => 'B2G1',
                'rules' => ['min_items' => 3, 'discount' => 'cheapest_free'],
                'is_active' => true,
            ]);

            // 2. Construction Project
            ConstructionProject::firstOrCreate(['name' => 'Smart Wing Extension'], [
                'status' => 'active',
                'budget' => 500000.00,
            ]);

            // 3. Insurance Policy
            InsurancePolicy::firstOrCreate(['number' => 'POL-2026-' . strtoupper($tenantId)], [
                'type' => 'osago',
                'expires_at' => now()->addYear(),
                'premium_amount' => 1500.00,
            ]);

            // 4. Update User Profile for AI Advisor
            User::where('email', 'admin@' . $tenantId . '.com')->update([
                'profile_data' => [
                    'clothing_size' => 'L',
                    'shoe_size' => '44',
                    'interests' => ['tech', 'cyberpunk', 'minimalism'],
                ]
            ]);

            tenancy()->end();
        }
    }
}
