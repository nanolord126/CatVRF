<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Test Database Seeder
 * Production 2026 CANON
 *
 * Creates minimal test data for all feature tests
 */
class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create test tenant
        \DB::table('tenants')->insert([
            'id' => 1,
            'name' => 'Test Tenant',
            'is_active' => true,
            'created_at' => now(),
        ]);

        // Create test users
        for ($i = 1; $i <= 5; $i++) {
            \App\Models\User::factory()->create([
                'id' => $i,
                'tenant_id' => 1,
                'email' => "user{$i}@test.com",
            ]);
        }

        // Create test wallets
        for ($i = 1; $i <= 5; $i++) {
            \DB::table('wallets')->insert([
                'tenant_id' => 1,
                'user_id' => $i,
                'current_balance' => 1000000, // 100 rubles
                'hold_amount' => 0,
                'created_at' => now(),
            ]);
        }

        // Create business group
        \DB::table('business_groups')->insert([
            'id' => 1,
            'tenant_id' => 1,
            'name' => 'Main Business',
            'is_active' => true,
            'created_at' => now(),
        ]);
    }
}
