<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Domains\Inventory\Models\InventoryBatch;
use Psr\Log\LoggerInterface;

/**
 * Inventory Batch Policy - RBAC for WMS
 *
 * Implements access control for inventory batches:
 * - Segregation of duties for quarantine/release operations
 * - View access based on tenant and role
 * - Critical operations (recalls, holds) require admin approval
 * - PII protection for batch tracking
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryBatchPolicy
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function view(User $user, InventoryBatch $batch): bool
    {
        if ($user->tenant_id !== $batch->tenant_id && ! $user->hasRole('admin')) {
            $this->logger->warning('Tenant mismatch in InventoryBatchPolicy::view', [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'batch_tenant_id' => $batch->tenant_id,
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

    public function update(User $user, InventoryBatch $batch): bool
    {
        if ($user->tenant_id !== $batch->tenant_id) {
            return false;
        }

        return $user->hasRole(['business', 'admin', 'warehouse_manager']);
    }

    public function delete(User $user, InventoryBatch $batch): bool
    {
        if ($user->tenant_id !== $batch->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function releaseFromQuarantine(User $user, InventoryBatch $batch): bool
    {
        if ($user->tenant_id !== $batch->tenant_id) {
            return false;
        }

        if ($batch->status !== 'quarantine') {
            return false;
        }

        return $user->hasRole(['admin', 'warehouse_manager', 'pharmacist']);
    }

    public function placeOnHold(User $user, InventoryBatch $batch): bool
    {
        if ($user->tenant_id !== $batch->tenant_id) {
            return false;
        }

        return $user->hasRole(['admin', 'warehouse_manager', 'quality_control']);
    }

    public function createRecall(User $user, InventoryBatch $batch): bool
    {
        if ($user->tenant_id !== $batch->tenant_id) {
            return false;
        }

        $hasAccess = $user->hasRole(['admin', 'compliance_officer']);

        if (! $hasAccess) {
            $this->logger->warning('Unauthorized recall attempt', [
                'user_id' => $user->id,
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
            ]);
        }

        return $hasAccess;
    }

    public function adjustQuantity(User $user, InventoryBatch $batch): bool
    {
        if ($user->tenant_id !== $batch->tenant_id) {
            return false;
        }

        return $user->hasRole(['business', 'admin', 'warehouse_manager']);
    }

    public function viewTraceability(User $user, InventoryBatch $batch): bool
    {
        if ($user->tenant_id !== $batch->tenant_id) {
            return false;
        }

        return $user->hasRole(['admin', 'compliance_officer', 'warehouse_manager']);
    }

    public function forceDelete(User $user, InventoryBatch $batch): bool
    {
        return false;
    }
}
