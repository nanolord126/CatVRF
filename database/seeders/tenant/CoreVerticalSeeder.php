<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\B2B\Supplier;
use App\Models\B2B\PurchaseOrder;
use App\Models\HR\HRExchangeTask;
use App\Models\Tenants\RestaurantMenuItem;
use App\Models\Tenants\RestaurantCategory;
use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiRide;

class CoreVerticalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Повар и Официант для HR/Food
        $staff = User::create([
            'name' => 'John Service',
            'email' => 'staff@tenant.local',
            'password' => bcrypt('password'),
            'role_code' => 'MASTER',
        ]);

        // 2. B2B Поставщики
        $supplier = Supplier::create([
            'name' => 'Global Foods Ltd',
            'tax_id' => '7701234567',
            'status' => 'ACTIVE',
            'credit_limit' => 50000.00,
            'email' => 'sales@globalfoods.com',
            'phone' => '+79001112233',
        ]);

        // 3. Меню ресторана
        $category = RestaurantCategory::create(['name' => 'Main Dishes', 'slug' => 'main-dishes']);
        RestaurantMenuItem::create([
            'restaurant_category_id' => $category->id,
            'name' => 'Wagyu Burger AI Specialized',
            'description' => 'Burger optimized by AI for best taste/cost ratio.',
            'price' => 25.00,
            'is_available' => true,
        ]);

        // 4. HR Биржа Заданий
        HRExchangeTask::create([
            'title' => 'Extra Shift: Waiter at Grand Plaza',
            'category' => 'RESTAURANT',
            'description' => 'Needed someone for a busy Friday night.',
            'reward_amount' => 150.00,
            'start_at' => now()->addDays(1)->setHour(18),
            'end_at' => now()->addDays(1)->setHour(23),
            'slots_available' => 2,
            'status' => 'OPEN',
        ]);

        // 5. Такси и ГИС
        $driver = TaxiDriver::create([
            'user_id' => $staff->id,
            'status' => 'available',
            'rating' => 4.9,
            'lat' => 55.7558,
            'lng' => 37.6173,
        ]);
    }
}
