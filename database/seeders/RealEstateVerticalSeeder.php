<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RealEstate\Property;
use App\Models\Tenant;

class RealEstateVerticalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создание тенанта для Недвижимости
        $tenantId = 'royal-estate-group';
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => 'Royal Estate & Land',
                'type' => 'real_estate',
            ]);
            $tenant->domains()->create(['domain' => 'property.localhost']);
        }

        // 2. Наполнение объектами (Продажа, Аренда, Коммерция)
        $properties = [
            [
                'tenant_id' => $tenantId,
                'type' => 'apartment',
                'name' => 'Skyline Penthouse v2026',
                'area' => 120.00,
                'price' => 450000.00,
                'geo_data' => ['district' => 'Central', 'floor' => 42],
                'amenities' => ['smart_home' => true, 'pool' => 'private'],
            ],
            [
                'tenant_id' => $tenantId,
                'type' => 'land',
                'name' => 'Green Valley Plot',
                'area' => 500.00,
                'price' => 120000.00,
                'geo_data' => ['district' => '郊区', 'type' => 'residential'],
                'amenities' => ['electricity' => true, 'water' => true],
            ],
            [
                'tenant_id' => $tenantId,
                'type' => 'commercial',
                'name' => 'Future Office Hub',
                'area' => 850.00,
                'price' => 2000000.00,
                'geo_data' => ['district' => 'Business', 'units' => 15],
                'amenities' => ['security' => 24/7, 'parking' => 'underground'],
            ],
            [
                'tenant_id' => $tenantId,
                'type' => 'rental',
                'name' => 'Digital Nomad Loft',
                'area' => 45.00,
                'price' => 1500.00,
                'geo_data' => ['district' => 'Modern', 'min_contract' => '3_months'],
                'amenities' => ['fiber_optics' => true, 'furniture' => 'minimalist'],
            ],
        ];

        foreach ($properties as $prop) {
            Property::create($prop);
        }

        $this->command->info('RealEstateVerticalSeeder: Properties seeded for Royal Estate.');
    }
}
