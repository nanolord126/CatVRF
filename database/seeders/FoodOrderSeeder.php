<?php

namespace Database\Seeders;

use App\Models\Domains\Food\FoodOrder;
use Illuminate\Database\Seeder;

class FoodOrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            ['total_amount' => 350, 'status' => 'confirmed'],
            ['total_amount' => 520, 'status' => 'delivered'],
            ['total_amount' => 180, 'status' => 'pending'],
        ];

        foreach ($orders as $order) {
            FoodOrder::factory()->create($order);
        }
    }
}
