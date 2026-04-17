<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\BooksAndLiterature\Models\BooksAndLiteratureOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BooksAndLiteratureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Books And Literature vertical...');

            for ($i = 1; $i <= 25; $i++) {
                BooksAndLiteratureOrder::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'customer_id' => rand(1, 10),
                    'items' => rand(1, 10),
                    'total_price' => rand(500, 20000),
                    'status' => ['pending', 'shipped', 'delivered'][rand(0, 2)],
                ]);
            }

            $this->command->info('Books And Literature vertical seeded successfully.');
        });
    }
}
