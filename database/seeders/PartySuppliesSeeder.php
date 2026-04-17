<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\PartySupplies\Models\PartySuppliesProduct;
use App\Domains\PartySupplies\Models\PartySuppliesOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PartySuppliesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Party Supplies vertical...');

            for ($i = 1; $i <= 25; $i++) {
                PartySuppliesProduct::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Product {$i}",
                    'description' => "Description for product {$i}",
                    'category' => ['balloons', 'decorations', 'tableware'][rand(0, 2)],
                    'price' => rand(200, 10000),
                    'stock' => rand(10, 100),
                    'status' => 'available',
                ]);

                PartySuppliesOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'customer_id' => rand(1, 10),
                    'items' => rand(1, 10),
                    'total_price' => rand(1000, 30000),
                    'status' => ['pending', 'shipped', 'delivered'][rand(0, 2)],
                ]);
            }

            $this->command->info('Party Supplies vertical seeded successfully.');
        });
    }
}
