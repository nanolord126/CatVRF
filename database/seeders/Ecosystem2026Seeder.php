<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class Ecosystem2026Seeder extends Seeder
{
    public function run(): void
    {
        Tenant::all()->each(function ($tenant) {
            tenancy()->initialize($tenant);

            // Тарифы ИИ
            DB::table('ai_quotas')->insertOrIgnore([
                ['tag' => 'free', 'daily_limit' => 3, 'price' => 0, 'duration_days' => 9999],
                ['tag' => '3days', 'daily_limit' => 30, 'price' => 490, 'duration_days' => 3],
                ['tag' => '30days', 'daily_limit' => 50, 'price' => 2990, 'duration_days' => 30],
            ]);

            // Базовые товары баьюти-шопа (Inventory)
            DB::table('beauty_products')->insertOrIgnore([
                ['name' => 'Premium Shampoo v2026', 'category' => 'Cosmetics', 'price' => 2500, 'stock' => 50],
                ['name' => 'Ionic Hair Dryer Pro', 'category' => 'Inventory', 'price' => 12000, 'stock' => 10],
                ['name' => 'Oud Wood Perfume', 'category' => 'Perfumery', 'price' => 8500, 'stock' => 25],
            ]);

            tenancy()->end();
        });
    }
}
