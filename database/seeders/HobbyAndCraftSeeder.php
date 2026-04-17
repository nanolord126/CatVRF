<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\HobbyAndCraft\Models\HobbyAndCraftKit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HobbyAndCraftSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Hobby And Craft vertical...');

            for ($i = 1; $i <= 25; $i++) {
                HobbyAndCraftKit::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Kit {$i}",
                    'description' => "Description for kit {$i}",
                    'category' => ['knitting', 'painting', 'woodworking'][rand(0, 2)],
                    'price' => rand(500, 15000),
                    'stock' => rand(10, 50),
                    'status' => 'available',
                ]);
            }

            $this->command->info('Hobby And Craft vertical seeded successfully.');
        });
    }
}
