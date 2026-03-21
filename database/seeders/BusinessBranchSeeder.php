<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BusinessBranch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Филиалы бизнеса (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class BusinessBranchSeeder extends Seeder
{
    public function run(): void
    {
        BusinessBranch::factory()
            ->count(30)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}