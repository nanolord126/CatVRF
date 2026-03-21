<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ElectronicsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table('tenants')->inRandomOrder()->value('id') ?? 1;

        $items = [
            ['name' => '4K Smart TV 65 inch', 'category' => 'televisions', 'brand' => 'TechVision', 'sku' => 'TV-4K-65', 'price' => 6999900],
            ['name' => 'Wireless Noise-Canceling Headphones', 'category' => 'audio', 'brand' => 'AudioPro', 'sku' => 'ANC-2000', 'price' => 2499900],
            ['name' => 'Latest Smartphone', 'category' => 'phones', 'brand' => 'SmartTech', 'sku' => 'ST-Pro-Max', 'price' => 11999900],
        ];

        foreach ($items as $item) {
            DB::table('electronics')->insert(array_merge($item, [
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'business_group_id' => null,
                'sku' => $item['sku'] . '-' . Str::random(4),
                'current_stock' => random_int(10, 100),
                'warranty_months' => 12,
                'rating' => random_int(40, 50) / 10,
                'review_count' => random_int(10, 200),
                'status' => 'active',
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
