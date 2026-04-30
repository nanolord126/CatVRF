<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\StockMovement;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Stock Movement Policy - RBAC for WMS
 *
 * Implements access control for stock movements:
 * - Segregation of duties between creation and approval
 * - View access based on tenant and role
 * - Critical operations require admin approval
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class StockMovementPolicy
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    public function view(User $user, StockMovement $movement): bool
    {
        if ($user->tenant_id !== $movement->tenant_id && ! $user->hasRole('admin')) {
            $this->logger->warning('Tenant mismatch in StockMovementPolicy::view', [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'movement_tenant_id' => $movement->tenant_id,
            ]);

            return false;
        }

        return $user->hasRole(['business', 'admin']);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['business', 'admin']) && $user->tenant_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['business', 'admin']) && $user->tenant_id !== null;
    }

    public function approve(User $user, StockMovement $movement): bool
    {
        if ($movement->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function reverse(User $user, StockMovement $movement): bool
    {
        if ($movement->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function delete(User $user, StockMovement $movement): bool
    {
        return false;
    }

    public function forceDelete(User $user, StockMovement $movement): bool
    {
        return false;
    }
}
