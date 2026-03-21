<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\BeautyProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Салон красоты (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class BeautyShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Инициализируем тенант для бьюти-сферы
        $tenant = Tenant::find('spa-beauty');

        if (!$tenant) {
            $tenant = Tenant::create([
                'id' => 'spa-beauty',
                'name' => 'Elite Spa & Beauty',
                'type' => 'beauty',
            ]);
            $tenant->domains()->create(['domain' => 'beauty.localhost']);
        }

        tenancy()->initialize($tenant);

        // 1. Создание Мастеров (Staff/HR)
        $master = User::firstOrCreate(
            ['email' => 'master@beauty.local'],
            [
                'name' => 'Elena Stylist',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Наполнение витрины товаров (Beauty Shop Inventory)
        $products = [
            [
                'name' => 'Professional Argan Oil v2026',
                'type' => 'cosmetics',
                'price' => 3500.00,
                'stock' => 45,
                'images' => json_encode(['https://placehold.co/600x400?text=Oil']),
                'tenant_id' => 'spa-beauty',
            ],
            [
                'name' => 'Titanium Hair Straightener',
                'type' => 'inventory',
                'price' => 15000.00,
                'stock' => 12,
                'images' => json_encode(['https://placehold.co/600x400?text=Straightener']),
                'tenant_id' => 'spa-beauty',
            ],
            [
                'name' => 'Organic Face Mask Set',
                'type' => 'cosmetics',
                'price' => 4200.00,
                'stock' => 30,
                'images' => json_encode(['https://placehold.co/600x400?text=Mask']),
                'tenant_id' => 'spa-beauty',
            ],
            [
                'name' => 'Rose Petal Perfume',
                'type' => 'perfumery',
                'price' => 8900.00,
                'stock' => 20,
                'images' => json_encode(['https://placehold.co/600x400?text=Perfume']),
                'tenant_id' => 'spa-beauty',
            ],
        ];

        foreach ($products as $productData) {
            BeautyProduct::updateOrCreate(
                ['name' => $productData['name']],
                $productData
            );
        }

        // 3. Услуги (если есть таблица beauty_services, иначе через общую структуру)
        // В текущих миграциях мы видели specialized_modules_tables, заглянем туда позже если нужно,
        // но для "Beauty Shop" товары — приоритет.

        tenancy()->end();

        $this->command->info('BeautyShopSeeder: Tenant "spa-beauty" seeded successfully with products and masters.');
    }
}
