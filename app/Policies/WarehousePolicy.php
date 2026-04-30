<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Domains\Shared\Inventory\Models\Warehouse;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Warehouse Policy - RBAC for WMS
 *
 * Implements fine-grained access control for warehouse operations:
 * - View access based on tenant and role
 * - Create/update restricted to business owners and admins
 * - Delete restricted to admins with audit trail
 * - Segregation of duties for critical operations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WarehousePolicy
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    public function view(User $user, Warehouse $warehouse): bool
    {
        if ($user->tenant_id !== $warehouse->tenant_id && ! $user->hasRole('admin')) {
            $this->logger->warning('Tenant mismatch in WarehousePolicy::view', [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'warehouse_tenant_id' => $warehouse->tenant_id,
            ]);

            return false;
        }

        return true;
    }

    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function create(User $user): bool
    {
        $allowed = $user->hasRole(['business', 'admin']) && $user->tenant_id !== null;

        if (! $allowed) {
            $this->logger->warning('Unauthorized warehouse creation attempt', [
                'user_id' => $user->id,
            ]);
        }

        return $allowed;
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        $allowed = $user->tenant_id === $warehouse->tenant_id &&
                   $user->hasRole(['business', 'admin']);

        if (! $allowed) {
            $this->logger->warning('Unauthorized warehouse update attempt', [
                'user_id' => $user->id,
                'warehouse_id' => $warehouse->id,
            ]);
        }

        return $allowed;
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        $allowed = $user->tenant_id === $warehouse->tenant_id &&
                   $user->hasRole('admin');

        if (! $allowed) {
            $this->logger->warning('Unauthorized warehouse delete attempt', [
                'user_id' => $user->id,
                'warehouse_id' => $warehouse->id,
            ]);
        }

        return $allowed;
    }

    public function manageLicenses(User $user, Warehouse $warehouse): bool
    {
        return $user->tenant_id === $warehouse->tenant_id &&
               $user->hasRole('admin');
    }

    public function manageStorageZones(User $user, Warehouse $warehouse): bool
    {
        return $user->tenant_id === $warehouse->tenant_id &&
               $user->hasRole(['business', 'admin']);
    }

    public function viewInventory(User $user, Warehouse $warehouse): bool
    {
        return $user->tenant_id === $warehouse->tenant_id &&
               $user->hasRole(['business', 'admin']);
    }

    public function performInventoryCheck(User $user, Warehouse $warehouse): bool
    {
        return $user->tenant_id === $warehouse->tenant_id &&
               $user->hasRole(['business', 'admin']);
    }

    public function manageNarcoticStorage(User $user, Warehouse $warehouse): bool
    {
        return $user->tenant_id === $warehouse->tenant_id &&
               $user->hasRole('admin') &&
               $this->hasValidLicense($warehouse, 'narcotic');
    }

    public function managePsychotropicStorage(User $user, Warehouse $warehouse): bool
    {
        return $user->tenant_id === $warehouse->tenant_id &&
               $user->hasRole('admin') &&
               $this->hasValidLicense($warehouse, 'psychotropic');
    }

    private function hasValidLicense(Warehouse $warehouse, string $licenseType): bool
    {
        $license = \Illuminate\Support\Facades\DB::table('warehouse_licenses')
            ->where('warehouse_id', $warehouse->id)
            ->where('license_type', $licenseType)
            ->where('is_active', true)
            ->where('expiry_date', '>', now())
            ->first();

        return $license !== null;
    }
}
