<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockMovement;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class TenantMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Roles if they don't exist
        $ownerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);

        // 2. Create Users
        $owner = User::firstOrCreate(
            ['email' => 'owner@tenant.com'],
            ['name' => 'Tenant Owner', 'password' => bcrypt('password')]
        );
        $owner->assignRole($ownerRole);

        $staff = User::firstOrCreate(
            ['email' => 'staff@tenant.com'],
            ['name' => 'Front Desk Staff', 'password' => bcrypt('password')]
        );
        $staff->assignRole($staffRole);

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
