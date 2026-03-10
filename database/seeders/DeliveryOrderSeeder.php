<?php

namespace Database\Seeders;

use App\Models\Domains\Delivery\DeliveryOrder;
use Illuminate\Database\Seeder;

class DeliveryOrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            ['distance' => 3.5, 'amount' => 150, 'status' => 'delivered'],
            ['distance' => 7.2, 'amount' => 280, 'status' => 'in_transit'],
            ['distance' => 2.1, 'amount' => 100, 'status' => 'pending'],
        ];

        foreach ($orders as $order) {
            DeliveryOrder::factory()->create($order);
        }
    }
}
