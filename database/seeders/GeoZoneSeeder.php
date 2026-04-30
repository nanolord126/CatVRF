<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GeoZone;
use Illuminate\Database\Seeder;

/**
 * Тестовые географические зоны (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        GeoZone::factory()->count(15)->create();
        GeoZone::factory()->count(8)->active()->create();
        GeoZone::factory()->count(3)->inactive()->create();
    }
}