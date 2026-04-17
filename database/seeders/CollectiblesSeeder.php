<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Collectibles\Models\CollectiblesItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CollectiblesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Collectibles vertical...');

            for ($i = 1; $i <= 30; $i++) {
                CollectiblesItem::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Item {$i}",
                    'description' => "Description for item {$i}",
                    'category' => ['coins', 'stamps', 'art'][rand(0, 2)],
                    'price' => rand(1000, 100000),
                    'condition' => ['mint', 'excellent', 'good'][rand(0, 2)],
                    'status' => 'available',
                ]);
            }

            $this->command->info('Collectibles vertical seeded successfully.');
        });
    }
}
