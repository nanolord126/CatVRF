<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\B2BProduct;
use App\Models\B2BManufacturer;
use Illuminate\Support\Str;

class B2BAIAnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $m = B2BManufacturer::first() ?? B2BManufacturer::create([
            'name' => 'EcoMed Systems 2026',
            'contact_email' => 'contact@ecomed.test',
            'registration_number' => 'REG-' . Str::upper(Str::random(8)),
            'contact_phone' => '+79001112233',
            'legal_address' => 'Moscow, Skolkovo',
            'category' => 'Medical',
            'correlation_id' => Str::uuid(),
        ]);

        $products = [
            ['name' => 'Portable Ultrasound Scanner V2', 'price' => 12500, 'category' => 'Medical'],
            ['name' => 'Sterile Nitrile Gloves (Box 100)', 'price' => 45, 'category' => 'Medical'],
            ['name' => 'Surgical Mask 3-Ply (Pack 50)', 'price' => 12, 'category' => 'Medical'],
            ['name' => 'Pet Nutrition Supplement Plus', 'price' => 85, 'category' => 'Pet Supplies'],
        ];

        foreach ($products as $p) {
            B2BProduct::firstOrCreate(
                ['name' => $p['name']],
                [
                    'manufacturer_id' => $m->id,
                    'sku' => strtoupper(Str::random(8)),
                    'base_wholesale_price' => $p['price'],
                    'unit' => 'unit',
                    'stock_quantity' => 100,
                    'min_order_quantity' => 1,
                    'specifications' => ['category' => $p['category']],
                    'correlation_id' => Str::uuid(),
                ]
            );
        }
    }
}
