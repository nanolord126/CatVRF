<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;

/**
 * Основные данные для tenant (роли, пользователи, активы).
 * НЕ ЗАПУСКАТЬ В PRODUCTION.
 */
final class TenantMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создаём роли
        Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web'], ['correlation_id' => (string) Str::uuid()]);
        Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web'], ['correlation_id' => (string) Str::uuid()]);
        Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web'], ['correlation_id' => (string) Str::uuid()]);

        // 2. Получаем первый tenant или создаём
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant Master',
            'correlation_id' => (string) Str::uuid(),
            'tags' => ['seeder:master', 'source:seeder'],
        ]);

        $this->command->info("Tenant '{$tenant->name}' создан");
    }
}

        // 3. Create ASSETS
        $tv = Product::updateOrCreate(
            ['sku' => 'AST-TV-43-001'],
            [
                'name' => 'TV Samsung 43"',
                'description' => 'Guest room entertainment system',
                'unit' => 'pcs',
                'is_consumable' => false,
                'category' => 'Electronics',
                'price' => 450.00,
            ]
        );

        $washingMachine = Product::updateOrCreate(
            ['sku' => 'AST-WM-LG-10'],
            [
                'name' => 'LG Washing Machine 10kg',
                'description' => 'Laundry room equipment',
                'unit' => 'pcs',
                'is_consumable' => false,
                'category' => 'Appliances',
                'price' => 800.00,
            ]
        );

        // 4. Create CONSUMABLES
        $shampoo = Product::updateOrCreate(
            ['sku' => 'CON-SHP-50ML'],
            [
                'name' => 'Shampoo 50ml (Hotel Line)',
                'description' => 'Single use guest shampoo',
                'unit' => 'pcs',
                'is_consumable' => true,
                'category' => 'Toiletries',
                'price' => 0.25,
            ]
        );

        $linen = Product::updateOrCreate(
            ['sku' => 'CON-LIN-DLX'],
            [
                'name' => 'Elite White Linen Set',
                'description' => 'High quality bed linen',
                'unit' => 'set',
                'is_consumable' => true,
                'category' => 'Textile',
                'price' => 15.00,
            ]
        );

        // 5. Initial Stock Arrival (IN)
        StockMovement::create([
            'product_id' => $shampoo->id,
            'type' => 'in',
            'quantity' => 500,
            'reason' => 'Monthly supply arrival',
            'user_id' => $owner->id,
            'is_approved' => true,
            'status' => 'approved',
            'correlation_id' => (string) Str::uuid(),
        ]);

        // 6. Linked Consumption (OUT) - Needs approval
        StockMovement::create([
            'product_id' => $shampoo->id,
            'type' => 'out',
            'quantity' => 2,
            'reason' => 'Guest check-in consumption',
            'user_id' => $staff->id,
            'reference_type' => 'Modules\Hotels\Models\Booking',
            'reference_id' => 101, // Mock ID
            'is_approved' => false,
            'status' => 'requires_approval',
            'correlation_id' => (string) Str::uuid(),
        ]);
        
        $this->command->info('Tenant assets and consumables seeded with Operational Canon logic.');
    }
}
