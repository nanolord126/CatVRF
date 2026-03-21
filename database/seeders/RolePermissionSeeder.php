<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = [
            'admin' => 'Platform administrator with full access',
            'business_owner' => 'Business owner - full business control',
            'manager' => 'Department/team manager',
            'accountant' => 'Finance & accounting specialist',
            'employee' => 'Regular employee/staff member',
            'customer' => 'End user/customer',
        ];

        foreach ($roles as $name => $description) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                [
                    'name' => $name,
                    'guard_name' => 'web',
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        echo "\n✅ Roles created/updated\n";
    }
}
