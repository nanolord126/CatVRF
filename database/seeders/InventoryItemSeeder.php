<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['sku' => 'SKU001', 'name' => 'Product A', 'current_stock' => 50],
            ['sku' => 'SKU002', 'name' => 'Product B', 'current_stock' => 30],
            ['sku' => 'SKU003', 'name' => 'Product C', 'current_stock' => 5],
        ];

        foreach ($items as $item) {
            InventoryItem::factory()->create(array_merge($item, ['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]));
        }
    }
}
