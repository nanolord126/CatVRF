<?php

namespace Database\Seeders;

use App\Models\Domains\Inventory\InventoryItem;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['sku' => 'SKU001', 'name' => 'Product A', 'cost_price' => 100, 'selling_price' => 250, 'quantity' => 50],
            ['sku' => 'SKU002', 'name' => 'Product B', 'cost_price' => 200, 'selling_price' => 450, 'quantity' => 30],
            ['sku' => 'SKU003', 'name' => 'Product C', 'cost_price' => 150, 'selling_price' => 350, 'quantity' => 5],
        ];

        foreach ($items as $item) {
            InventoryItem::factory()->create($item);
        }
    }
}
