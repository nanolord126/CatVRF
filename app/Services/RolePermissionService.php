<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * RolePermissionService — Сервис управления ролями и разрешениями
 * 
 * RBAC для Supermarket вертикали
 */
final class RolePermissionService
{
    use WithAuditLogging;
    use WithTelemetry;

    // Роли
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_SELLER = 'seller';
    public const ROLE_COURIER = 'courier';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ANALYST = 'analyst';

    // Разрешения
    // Orders
    public const PERMISSION_ORDERS_CREATE = 'orders.create';
    public const PERMISSION_ORDERS_VIEW_OWN = 'orders.view_own';
    public const PERMISSION_ORDERS_VIEW_ALL = 'orders.view_all';
    public const PERMISSION_ORDERS_UPDATE_STATUS = 'orders.update_status';
    public const PERMISSION_ORDERS_CANCEL = 'orders.cancel';
    public const PERMISSION_ORDERS_REFUND = 'orders.refund';

    // Products
    public const PERMISSION_PRODUCTS_MANAGE = 'products.manage';
    public const PERMISSION_PRODUCTS_VIEW = 'products.view';
    public const PERMISSION_PRODUCTS_DELETE = 'products.delete';

    // Pricing
    public const PERMISSION_PRICING_CONFIGURE = 'pricing.configure';
    public const PERMISSION_PRICING_VIEW = 'pricing.view';

    // Reviews
    public const PERMISSION_REVIEWS_CREATE = 'reviews.create';
    public const PERMISSION_REVIEWS_MODERATE_OWN = 'reviews.moderate_own';
    public const PERMISSION_REVIEWS_MODERATE_ALL = 'reviews.moderate_all';
    public const PERMISSION_REVIEWS_VIEW_ALL = 'reviews.view_all';

    // Analytics
    public const PERMISSION_ANALYTICS_VIEW_OWN = 'analytics.view_own';
    public const PERMISSION_ANALYTICS_VIEW_ALL = 'analytics.view_all';
    public const PERMISSION_ANALYTICS_EXPORT = 'analytics.export';

    // Users
    public const PERMISSION_USERS_MANAGE = 'users.manage';
    public const PERMISSION_USERS_VIEW = 'users.view';
    public const PERMISSION_USERS_DELETE = 'users.delete';

    // Roles
    public const PERMISSION_ROLES_MANAGE = 'roles.manage';
    public const PERMISSION_ROLES_VIEW = 'roles.view';

    // Returns
    public const PERMISSION_RETURNS_CREATE = 'returns.create';
    public const PERMISSION_RETURNS_APPROVE = 'returns.approve';
    public const PERMISSION_RETURNS_REJECT = 'returns.reject';
    public const PERMISSION_RETURNS_VIEW_ALL = 'returns.view_all';

    /**
     * Матрица ролей и разрешений
     */
    private array $rolePermissions = [
        self::ROLE_CUSTOMER => [
            self::PERMISSION_ORDERS_CREATE,
            self::PERMISSION_ORDERS_VIEW_OWN,
            self::PERMISSION_PRODUCTS_VIEW,
            self::PERMISSION_REVIEWS_CREATE,
            self::PERMISSION_REVIEWS_VIEW_ALL,
            self::PERMISSION_ANALYTICS_VIEW_OWN,
            self::PERMISSION_RETURNS_CREATE,
        ],
        self::ROLE_SELLER => [
            self::PERMISSION_ORDERS_VIEW_ALL,
            self::PERMISSION_ORDERS_UPDATE_STATUS,
            self::PERMISSION_PRODUCTS_MANAGE,
            self::PERMISSION_PRODUCTS_VIEW,
            self::PERMISSION_PRICING_CONFIGURE,
            self::PERMISSION_PRICING_VIEW,
            self::PERMISSION_REVIEWS_MODERATE_OWN,
            self::PERMISSION_REVIEWS_VIEW_ALL,
            self::PERMISSION_ANALYTICS_VIEW_OWN,
            self::PERMISSION_RETURNS_APPROVE,
            self::PERMISSION_RETURNS_REJECT,
            self::PERMISSION_RETURNS_VIEW_ALL,
        ],
        self::ROLE_COURIER => [
            self::PERMISSION_ORDERS_VIEW_ALL,
            self::PERMISSION_ORDERS_UPDATE_STATUS,
            self::PERMISSION_PRODUCTS_VIEW,
        ],
        self::ROLE_MODERATOR => [
            self::PERMISSION_ORDERS_VIEW_ALL,
            self::PERMISSION_PRODUCTS_VIEW,
            self::PERMISSION_REVIEWS_MODERATE_ALL,
            self::PERMISSION_REVIEWS_VIEW_ALL,
            self::PERMISSION_RETURNS_APPROVE,
            self::PERMISSION_RETURNS_REJECT,
            self::PERMISSION_RETURNS_VIEW_ALL,
        ],
        self::ROLE_ANALYST => [
            self::PERMISSION_ORDERS_VIEW_ALL,
            self::PERMISSION_PRODUCTS_VIEW,
            self::PERMISSION_ANALYTICS_VIEW_ALL,
            self::PERMISSION_ANALYTICS_EXPORT,
        ],
        self::ROLE_ADMIN => [
            '*', // All permissions
        ],
    ];

    /**
     * Проверить имеет ли пользователь разрешение
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $userRoles = $this->getUserRoles($userId);

        foreach ($userRoles as $role) {
            if ($this->roleHasPermission($role, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить имеет ли роль разрешение
     */
    public function roleHasPermission(string $role, string $permission): bool
    {
        $permissions = $this->rolePermissions[$role] ?? [];

        // Wildcard для admin
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Получить роли пользователя
     */
    public function getUserRoles(int $userId): array
    {
        $cacheKey = "user:roles:{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($userId) {
            // TODO: Запрос к БД
            // return DB::table('user_roles')->where('user_id', $userId)->pluck('role')->toArray();
            return [self::ROLE_CUSTOMER]; // mock
        });
    }

    /**
     * Назначить роль пользователю
     */
    public function assignRole(int $userId, string $role, ?int $tenantId = null): bool
    {
        // TODO: Запись в БД
        Cache::forget("user:roles:{$userId}");

        $this->logAction('role_assigned', null, [
            'user_id' => $userId,
            'role' => $role,
            'tenant_id' => $tenantId,
        ], null, $userId);

        return true;
    }

    /**
     * Удалить роль у пользователя
     */
    public function removeRole(int $userId, string $role): bool
    {
        // TODO: Удаление из БД
        Cache::forget("user:roles:{$userId}");

        $this->logAction('role_removed', null, [
            'user_id' => $userId,
            'role' => $role,
        ], null, $userId);

        return true;
    }

    /**
     * Получить все разрешения роли
     */
    public function getRolePermissions(string $role): array
    {
        return $this->rolePermissions[$role] ?? [];
    }

    /**
     * Получить все роли
     */
    public function getAllRoles(): array
    {
        return [
            self::ROLE_CUSTOMER => [
                'name' => 'Покупатель',
                'description' => 'Обычный покупатель маркетплейса',
            ],
            self::ROLE_SELLER => [
                'name' => 'Продавец',
                'description' => 'Магазин/продавец на маркетплейсе',
            ],
            self::ROLE_COURIER => [
                'name' => 'Курьер',
                'description' => 'Курьер для доставки заказов',
            ],
            self::ROLE_MODERATOR => [
                'name' => 'Модератор',
                'description' => 'Модератор контента платформы',
            ],
            self::ROLE_ANALYST => [
                'name' => 'Аналитик',
                'description' => 'Аналитик данных',
            ],
            self::ROLE_ADMIN => [
                'name' => 'Администратор',
                'description' => 'Полный доступ к системе',
            ],
        ];
    }
}
