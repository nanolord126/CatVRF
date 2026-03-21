<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\Cosmetics;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Косметика (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class CosmeticsSeeder extends Seeder
{
    public function run(): void
    {
        Cosmetics::factory()
            ->count(3)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}