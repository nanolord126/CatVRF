<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Роли и разрешения (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super-admin' => ['manage-all', 'access-admin-panel', 'manage-tenants'],
            'tenant-owner' => ['manage-tenant', 'access-tenant-panel', 'manage-employees'],
            'tenant-manager' => ['access-tenant-panel', 'view-analytics'],
            'b2b-supplier' => ['access-b2b-panel', 'manage-b2b-catalog', 'view-invoices'],
            'support-agent' => ['access-admin-panel', 'resolve-tickets', 'view-users'],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            foreach ($perms as $pName) {
                Permission::firstOrCreate(['name' => $pName, 'guard_name' => 'web']);
                $role->givePermissionTo($pName);
            }
        }
    }
}
