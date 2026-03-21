<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\Concert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Концерты (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class ConcertSeeder extends Seeder
{
    public function run(): void
    {
        Concert::factory()
            ->count(3)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}