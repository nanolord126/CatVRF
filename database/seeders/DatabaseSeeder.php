<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Основной сидер для инициализации БД тестовыми данными.
 * НЕ ЗАПУСКАТЬ В PRODUCTION.
 */
final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Центральный админ (без tenant_id для платформы)
        User::factory()->create([
            'email' => 'admin@catvrf.local',
            'name' => 'Super Admin',
            'password' => Hash::make('admin123'),
            'tenant_id' => null,
            'correlation_id' => (string) Str::uuid(),
            'tags' => ['user:superadmin', 'source:seeder'],
        ]);

        // Создаём основные tenants через factory
        $hotelTenant = Tenant::factory()->create([
            'name' => 'Grand Hotel Luxury',
            'slug' => 'grand-hotel',
            'tags' => ['vertical:hotel', 'source:seeder'],
        ]);

        $beautyTenant = Tenant::factory()->create([
            'name' => 'Elite Spa & Beauty',
            'slug' => 'spa-beauty',
            'tags' => ['vertical:beauty', 'source:seeder'],
        ]);

        $this->command->info('Основные tenants созданы');

        // Вызываем все сидеры
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            // Verticals
            TaxiRideSeeder::class,
            FoodOrderSeeder::class,
            HotelBookingSeeder::class,
            SportsMembershipSeeder::class,
            MedicalCardSeeder::class,
            DeliveryOrderSeeder::class,
            InventoryItemSeeder::class,
            AdCampaignSeeder::class,
            GeoZoneSeeder::class,
            CourseSeeder::class,
            SalonSeeder::class,
            PropertySeeder::class,
            InsurancePolicySeeder::class,
            MessageSeeder::class,
            // New Food Verticals
            FarmDirectSeeder::class,
            HealthyFoodSeeder::class,
            ConfectionerySeeder::class,
            MeatShopsSeeder::class,
            OfficeCateringSeeder::class,
            // New Goods Verticals
            FurnitureSeeder::class,
            ElectronicsSeeder::class,
            ToysKidsSeeder::class,
            AutoVerticalSeeder::class, // Includes AutoParts
            PharmacySeeder::class,
        ]);

        $this->command->info('✓ Все сидеры успешно выполнены');
    }
}
