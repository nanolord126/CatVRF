<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FlowersVerticalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создание или обновление тенанта
        $tenantId = 'bloom-flowers';
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => 'Bloom & Bouquet',
                'type' => 'flowers',
            ]);
            $tenant->domains()->create(['domain' => 'flowers.localhost']);
        }

        tenancy()->initialize($tenant);

        // 2. Создание флориста
        $florist = User::where('email', 'florist@bloom.local')->first();
        if (!$florist) {
            $florist = User::create([
                'name' => 'Rose Garland',
                'email' => 'florist@bloom.local',
                'password' => bcrypt('password'),
            ]);
        }

        // 3. Товары (согласно миграции marketplace_verticals)
        if (Schema::hasTable('flowers_shops')) {
            DB::table('flowers_shops')->insert([
                'name' => 'Garden of Roses',
                'address' => 'Flower Blvd, 12',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('flowers_items')) {
            DB::table('flowers_items')->insert([
                'name' => 'Royal Rose Bouquet',
                'description' => '21 Premium Red Roses',
                'price' => 5500.00,
                'composition' => json_encode(['Red Rose' => 21, 'Greenery' => 5]),
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. (Дополнительно) Можно добавить продукты, если реализована таблица, 
        // но в basic_verticals миграции пока только магазины.

        tenancy()->end();

        $this->command->info('FlowersVerticalSeeder: Tenant "bloom-flowers" seeded with flower shop and florist.');
    }
}
