<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\Clinic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Клиники (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        Clinic::factory()
            ->count(3)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}