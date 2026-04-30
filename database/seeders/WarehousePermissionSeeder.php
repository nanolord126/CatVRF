<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Warehouse Permission Seeder
 * 
 * Создает все необходимые разрешения и роли для вертикали склада
 * в соответствии с RBAC требованиями
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class WarehousePermissionSeeder extends Seeder
{
    /**
     * Роли для склада
     */
    private const ROLES = [
        'warehouse_admin' => 'Полный доступ ко всем операциям склада',
        'warehouse_manager' => 'Управление складом без удаления',
        'warehouse_worker' => 'Операции приема/отгрузки, движения товаров',
        'warehouse_viewer' => 'Только просмотр',
        'warehouse_auditor' => 'Аудит и инвентаризация',
    ];

    /**
     * Разрешения для операций со складами
     */
    private const WAREHOUSE_PERMISSIONS = [
        'view warehouses',
        'create warehouses',
        'edit warehouses',
        'delete warehouses',
        'view warehouse statistics',
        'manage warehouse settings',
    ];

    /**
     * Разрешения для операций с зонами
     */
    private const ZONE_PERMISSIONS = [
        'view zones',
        'create zones',
        'edit zones',
        'delete zones',
        'manage zone capacity',
    ];

    /**
     * Разрешения для операций с ячейками
     */
    private const BIN_PERMISSIONS = [
        'view bins',
        'create bins',
        'edit bins',
        'delete bins',
        'scan bins',
    ];

    /**
     * Разрешения для операций с товарами
     */
    private const PRODUCT_PERMISSIONS = [
        'view products',
        'create products',
        'edit products',
        'delete products',
        'manage product catalog',
    ];

    /**
     * Разрешения для движений товаров
     */
    private const MOVEMENT_PERMISSIONS = [
        'view movements',
        'create receipt movements',
        'create transfer movements',
        'create shipment movements',
        'create adjustment movements',
        'approve movements',
        'reverse movements',
    ];

    /**
     * Разрешения для инвентаризации
     */
    private const INVENTORY_PERMISSIONS = [
        'view inventory counts',
        'create inventory counts',
        'perform inventory counts',
        'approve inventory counts',
        'view inventory discrepancies',
    ];

    /**
     * Разрешения для партий
     */
    private const BATCH_PERMISSIONS = [
        'view batches',
        'create batches',
        'edit batches',
        'delete batches',
        'quarantine batches',
        'expire batches',
    ];

    /**
     * Разрешения для документов
     */
    private const DOCUMENT_PERMISSIONS = [
        'view documents',
        'create documents',
        'sign documents',
        'approve documents',
        'reject documents',
        'archive documents',
    ];

    /**
     * Разрешения для отчетов
     */
    private const REPORT_PERMISSIONS = [
        'view reports',
        'export reports',
        'view audit logs',
    ];

    /**
     * Специальные разрешения
     */
    private const SPECIAL_PERMISSIONS = [
        'manage controlled substances',
        'override capacity limits',
        'perform emergency operations',
        'view sensitive data',
    ];

    public function run(): void
    {
        // Создаем все разрешения
        $allPermissions = array_merge(
            self::WAREHOUSE_PERMISSIONS,
            self::ZONE_PERMISSIONS,
            self::BIN_PERMISSIONS,
            self::PRODUCT_PERMISSIONS,
            self::MOVEMENT_PERMISSIONS,
            self::INVENTORY_PERMISSIONS,
            self::BATCH_PERMISSIONS,
            self::DOCUMENT_PERMISSIONS,
            self::REPORT_PERMISSIONS,
            self::SPECIAL_PERMISSIONS
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Создаем роли и назначаем разрешения
        $this->createWarehouseAdminRole();
        $this->createWarehouseManagerRole();
        $this->createWarehouseWorkerRole();
        $this->createWarehouseViewerRole();
        $this->createWarehouseAuditorRole();

        $this->command->info('Warehouse permissions and roles seeded successfully.');
    }

    /**
     * Роль Администратора склада - полный доступ
     */
    private function createWarehouseAdminRole(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'warehouse_admin',
            'guard_name' => 'web',
        ]);

        // Все разрешения
        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);

        $this->command->info('Role "warehouse_admin" created with all permissions.');
    }

    /**
     * Роль Менеджера склада - управление без удаления
     */
    private function createWarehouseManagerRole(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'warehouse_manager',
            'guard_name' => 'web',
        ]);

        // Все разрешения кроме delete
        $permissions = Permission::where('name', 'not like', '%delete%')
            ->where('name', '!=', 'manage controlled substances')
            ->where('name', '!=', 'override capacity limits')
            ->get();

        $role->syncPermissions($permissions);

        $this->command->info('Role "warehouse_manager" created.');
    }

    /**
     * Роль Работника склада - операции приемки/отгрузки
     */
    private function createWarehouseWorkerRole(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'warehouse_worker',
            'guard_name' => 'web',
        ]);

        $workerPermissions = array_merge(
            self::WAREHOUSE_PERMISSIONS, // Только просмотр складов
            self::ZONE_PERMISSIONS, // Только просмотр зон
            self::BIN_PERMISSIONS, // Сканирование ячеек
            self::PRODUCT_PERMISSIONS, // Только просмотр товаров
            self::MOVEMENT_PERMISSIONS, // Создание движений
            self::INVENTORY_PERMISSIONS, // Проведение инвентаризации
            self::BATCH_PERMISSIONS, // Просмотр партий
        );

        // Фильтруем только нужные разрешения
        $permissions = Permission::whereIn('name', $workerPermissions)
            ->where(function ($query) {
                $query->where('name', 'not like', '%create%')
                    ->where('name', 'not like', '%edit%')
                    ->where('name', 'not like', '%delete%')
                    ->orWhere('name', 'like', '%movement%')
                    ->orWhere('name', 'like', '%scan%')
                    ->orWhere('name', 'like', '%perform%');
            })
            ->get();

        $role->syncPermissions($permissions);

        $this->command->info('Role "warehouse_worker" created.');
    }

    /**
     * Роль Просмотрщика - только чтение
     */
    private function createWarehouseViewerRole(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'warehouse_viewer',
            'guard_name' => 'web',
        ]);

        $viewerPermissions = array_filter([
            'view warehouses',
            'view zones',
            'view bins',
            'view products',
            'view movements',
            'view inventory counts',
            'view batches',
            'view documents',
            'view reports',
        ]);

        $permissions = Permission::whereIn('name', $viewerPermissions)->get();
        $role->syncPermissions($permissions);

        $this->command->info('Role "warehouse_viewer" created.');
    }

    /**
     * Роль Аудитора - инвентаризация и аудит
     */
    private function createWarehouseAuditorRole(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'warehouse_auditor',
            'guard_name' => 'web',
        ]);

        $auditorPermissions = array_merge(
            ['view warehouses', 'view zones', 'view bins', 'view products'],
            self::INVENTORY_PERMISSIONS,
            self::REPORT_PERMISSIONS,
            ['view audit logs']
        );

        $permissions = Permission::whereIn('name', $auditorPermissions)->get();
        $role->syncPermissions($permissions);

        $this->command->info('Role "warehouse_auditor" created.');
    }
}
