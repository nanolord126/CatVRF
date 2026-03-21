<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Ресторанная вертикаль (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class RestaurantVerticalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создание или обновление тенанта
        $tenantId = 'resto-deluxe';
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => 'Resto Deluxe & Grill',
                'type' => 'restaurant',
            ]);
            $tenant->domains()->create(['domain' => 'resto.localhost']);
        }

        tenancy()->initialize($tenant);

        // 2. Создание главного менеджера ресторана
        $manager = User::where('email', 'manager@resto.local')->first();
        if (!$manager) {
            $manager = User::create([
                'name' => 'Ivan Restoman',
                'email' => 'manager@resto.local',
                'password' => bcrypt('password'),
            ]);
        }

        // 3. Наполнение данными ресторана
        if (Schema::hasTable('restaurants')) {
            $restaurantId = DB::table('restaurants')->insertGetId([
                'name' => 'Resto Deluxe Main',
                'address' => 'Central Ave, 42',
                'phone' => '+7-999-123-45-67',
                'location' => json_encode(['lat' => 55.7558, 'lng' => 37.6173]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Меню
            $menuItems = [
                ['name' => 'Signature Steak', 'price' => 2500.00],
                ['name' => 'Caesar Salad', 'price' => 850.00],
                ['name' => 'Red Wine Bottle', 'price' => 4500.00],
                ['name' => 'Mineral Water', 'price' => 300.00],
            ];

            foreach ($menuItems as $item) {
                if (Schema::hasTable('restaurant_menus')) {
                    DB::table('restaurant_menus')->insert(array_merge($item, [
                        'restaurant_id' => $restaurantId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        }
        
        if (Schema::hasTable('restaurant_tables')) {
            DB::table('restaurant_tables')->insert([
                'number' => '1',
                'capacity' => 4,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        tenancy()->end();

        $this->command->info('RestaurantVerticalSeeder: Tenant "resto-deluxe" seeded with restaurant and menu.');
    }
}
