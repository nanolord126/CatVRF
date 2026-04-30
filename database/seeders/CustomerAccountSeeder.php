<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\CustomerAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Аккаунты клиентов (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class CustomerAccountSeeder extends Seeder
{
    public function run(): void
    {
        CustomerAccount::factory()
            ->count(10)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}