<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\GroceryAndDelivery\Models\GroceryAndDeliveryOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GroceryAndDeliverySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Grocery And Delivery vertical...');

            for ($i = 1; $i <= 25; $i++) {
                GroceryAndDeliveryOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'customer_id' => rand(1, 10),
                    'items' => rand(1, 20),
                    'total_price' => rand(500, 15000),
                    'delivery_address' => "Address {$i}",
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'status' => ['pending', 'in_progress', 'delivered'][rand(0, 2)],
                ]);
            }

            $this->command->info('Grocery And Delivery vertical seeded successfully.');
        });
    }
}
