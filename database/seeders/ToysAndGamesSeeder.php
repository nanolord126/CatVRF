<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\ToysAndGames\Models\ToysAndGamesProduct;
use App\Domains\ToysAndGames\Models\ToysAndGamesOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ToysAndGamesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Toys And Games vertical...');

            for ($i = 1; $i <= 25; $i++) {
                ToysAndGamesProduct::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Toy {$i}",
                    'description' => "Description for toy {$i}",
                    'category' => ['toys', 'games', 'puzzles'][rand(0, 2)],
                    'price' => rand(500, 20000),
                    'age_range' => ['0-3', '3-6', '6-12', '12+'][rand(0, 3)],
                    'stock' => rand(10, 100),
                    'status' => 'available',
                ]);

                ToysAndGamesOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'customer_id' => rand(1, 10),
                    'items' => rand(1, 5),
                    'total_price' => rand(1000, 50000),
                    'status' => ['pending', 'shipped', 'delivered'][rand(0, 2)],
                ]);
            }

            $this->command->info('Toys And Games vertical seeded successfully.');
        });
    }
}
