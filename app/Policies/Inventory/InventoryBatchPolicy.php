<?php

declare(strict_types=1);

namespace App\Policies\Inventory;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Inventory Batch Policy
 *
 * Role-based access control for inventory batches:
 * - Batch tracking
 * - Quarantine management
 * - Recall management
 * - Serial number control
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
class InventoryBatchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view batches
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('inventory_manager')
            || $user->hasRole('warehouse_worker');
    }

    /**
     * Determine if user can view specific batch
     *
     * @param  User  $user
     * @param  int  $batchId
     * @return bool
     */
    public function view(User $user, int $batchId): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->hasRole('admin') || $user->hasRole('warehouse_manager')) {
            return true;
        }

        $batch = \DB::table('inventory_batches')->where('id', $batchId)->first();

        if (! $batch) {
            return false;
        }

        if ($batch->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $batch->warehouse_id === $user->warehouse_id;
    }

    /**
     * Determine if user can create batches
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('inventory_manager');
    }

    /**
     * Determine if user can update batches
     *
     * @param  User  $user
     * @param  int  $batchId
     * @return bool
     */
    public function update(User $user, int $batchId): bool
    {
        return $this->create($user) && $this->view($user, $batchId);
    }

    /**
     * Determine if user can delete batches
     *
     * @param  User  $user
     * @param  int  $batchId
     * @return bool
     */
    public function delete(User $user, int $batchId): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can place batch in quarantine
     *
     * @param  User  $user
     * @param  int  $batchId
     * @return bool
     */
    public function quarantine(User $user, int $batchId): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('inventory_manager');
    }

    /**
     * Determine if user can release batch from quarantine
     *
     * @param  User  $user
     * @param  int  $batchId
     * @return bool
     */
    public function releaseFromQuarantine(User $user, int $batchId): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager');
    }

    /**
     * Determine if user can create recall
     *
     * @param  User  $user
     * @return bool
     */
    public function createRecall(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('compliance_officer');
    }

    /**
     * Determine if user can close recall
     *
     * @param  User  $user
     * @return bool
     */
    public function closeRecall(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager');
    }

    /**
     * Determine if user can view traceability
     *
     * @param  User  $user
     * @param  int  $batchId
     * @return bool
     */
    public function viewTraceability(User $user, int $batchId): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('compliance_officer');
    }
}
