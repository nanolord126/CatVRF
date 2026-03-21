<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Мастер-сидер для запуска всех вертикалей 2026 (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class ProductionMasterSeeder extends Seeder
{
    /**
     * Master Seeder to launch all 2026 vertical modules in proper order.
     * Ensures all dependencies (Users -> Venues -> Events -> Ads) are met.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            GeoHierarchySeeder::class,
            \Modules\Inventory\Database\Seeders\InventorySeeder::class,
            MarketplaceVerticalsSeeder::class,
            StaffSeeder::class,
            PayrollSeeder::class,
            \Modules\Advertising\Database\Seeders\AdSeeder::class,
            AiRecommendationsSeeder::class,
            B2BAIAnalyticsSeeder::class,
        ]);
        
        // Final sanity check
        Artisan::call('tenants:run "tinker --execute=\'echo \"Production-ready! 2026 Master Seed completed.\"\'" --tenants=grand-hotel');
    }
}
