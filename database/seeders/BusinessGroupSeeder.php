<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BusinessGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Группы бизнеса (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class BusinessGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // BusinessGroup::factory()->count(10)->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}
