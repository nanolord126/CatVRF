<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Тестовые пользователи (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class UserSeeder extends Seeder
{
    /**
     * Seed the users table.
     */
    public function run(): void
    {
        // Создаём тестовый тенант
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
        ]);

        // Создаём владельца бизнеса для тестового тенанта
        User::factory()
            ->owner()
            ->create([
                'tenant_id' => $tenant->id,
                'email' => 'owner@catvrf.local',
                'name' => 'Business Owner',
                'correlation_id' => (string) Str::uuid(),
                'tags' => ['user:owner', 'source:seeder', 'test'],
            ]);

        // Создаём обычных пользователей
        User::factory()
            ->count(10)
            ->create([
                'tenant_id' => $tenant->id,
                'correlation_id' => (string) Str::uuid(),
                'tags' => ['user:regular', 'source:seeder', 'test'],
            ]);

        // Создаём неактивных пользователей
        User::factory()
            ->inactive()
            ->count(5)
            ->create([
                'tenant_id' => $tenant->id,
                'correlation_id' => (string) Str::uuid(),
                'tags' => ['user:inactive', 'source:seeder', 'test'],
            ]);
    }
}