<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\AnimalProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Товары для животных (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class AnimalProductSeeder extends Seeder
{
    public function run(): void
    {
        AnimalProduct::factory()
            ->count(7)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}