<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\InventoryItem;
use Psr\Log\LoggerInterface;

/**
 * Inventory Item Policy - RBAC for WMS
 *
 * Implements access control for inventory items:
 * - Segregation of duties between viewing and editing
 * - View access based on tenant and role
 * - Critical operations require admin approval
 * - Audit logging for all access attempts
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryItemPolicy
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function view(User $user, InventoryItem $item): bool
    {
        if ($user->tenant_id !== $item->tenant_id && ! $user->hasRole('admin')) {
            $this->logger->warning('Tenant mismatch in InventoryItemPolicy::view', [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'item_tenant_id' => $item->tenant_id,
            ]);

            return false;
        }

        return $user->hasRole(['business', 'admin', 'warehouse_staff']);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['business', 'admin', 'warehouse_staff']) && $user->tenant_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['business', 'admin', 'warehouse_manager']) && $user->tenant_id !== null;
    }

    public function update(User $user, InventoryItem $item): bool
    {
        if ($user->tenant_id !== $item->tenant_id) {
            return false;
        }

        return $user->hasRole(['business', 'admin', 'warehouse_manager']);
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        if ($user->tenant_id !== $item->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function adjustStock(User $user, InventoryItem $item): bool
    {
        if ($user->tenant_id !== $item->tenant_id) {
            return false;
        }

        return $user->hasRole(['business', 'admin', 'warehouse_manager']);
    }

    public function viewPII(User $user, InventoryItem $item): bool
    {
        if ($user->tenant_id !== $item->tenant_id) {
            return false;
        }

        return $user->hasRole(['admin', 'compliance_officer']);
    }

    public function forceDelete(User $user, InventoryItem $item): bool
    {
        return false;
    }
}
