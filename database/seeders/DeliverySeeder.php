<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Delivery\Models\DeliveryOrder;
use App\Domains\Delivery\Models\DeliveryCourier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Delivery vertical...');

            for ($i = 1; $i <= 20; $i++) {
                DeliveryCourier::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Courier {$i}",
                    'phone' => "+7900{$i}0000",
                    'vehicle_type' => ['bike', 'car', 'walk'][rand(0, 2)],
                    'status' => 'available',
                ]);

                DeliveryOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'order_number' => "ORD-{$i}",
                    'courier_id' => $i,
                    'customer_id' => rand(1, 10),
                    'address' => "Address {$i}",
                    'delivery_fee' => rand(200, 1000),
                    'status' => ['pending', 'in_progress', 'delivered'][rand(0, 2)],
                ]);
            }

            $this->command->info('Delivery vertical seeded successfully.');
        });
    }
}
