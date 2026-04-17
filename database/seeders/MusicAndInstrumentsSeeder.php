<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\MusicAndInstruments\Models\MusicAndInstrumentsProduct;
use App\Domains\MusicAndInstruments\Models\MusicAndInstrumentsOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MusicAndInstrumentsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Music And Instruments vertical...');

            for ($i = 1; $i <= 25; $i++) {
                MusicAndInstrumentsProduct::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Product {$i}",
                    'description' => "Description for product {$i}",
                    'category' => ['guitars', 'keyboards', 'drums'][rand(0, 2)],
                    'price' => rand(2000, 100000),
                    'stock' => rand(5, 50),
                    'status' => 'available',
                ]);

                MusicAndInstrumentsOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'customer_id' => rand(1, 10),
                    'items' => rand(1, 5),
                    'total_price' => rand(2000, 500000),
                    'status' => ['pending', 'shipped', 'delivered'][rand(0, 2)],
                ]);
            }

            $this->command->info('Music And Instruments vertical seeded successfully.');
        });
    }
}
