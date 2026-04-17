<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Marketplace\Models\MarketplaceListing;
use App\Domains\Marketplace\Models\MarketplaceOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Marketplace vertical...');

            for ($i = 1; $i <= 25; $i++) {
                MarketplaceListing::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'title' => "Listing {$i}",
                    'description' => "Description for listing {$i}",
                    'seller_id' => rand(1, 10),
                    'price' => rand(1000, 100000),
                    'category' => ['electronics', 'clothing', 'home'][rand(0, 2)],
                    'status' => 'active',
                ]);

                MarketplaceOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'listing_id' => $i,
                    'buyer_id' => rand(1, 10),
                    'quantity' => rand(1, 5),
                    'total_price' => rand(1000, 500000),
                    'status' => ['pending', 'paid', 'shipped'][rand(0, 2)],
                ]);
            }

            $this->command->info('Marketplace vertical seeded successfully.');
        });
    }
}
