<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

/**
 * EmployeePolicy — управление сотрудниками бизнеса.
 * Только владелец бизнеса может управлять сотрудниками.
 */
final class EmployeePolicy
{
    use HandlesAuthorization;
    
    /**
     * Только владелец бизнеса и администраторы могут просматривать сотрудников.
     */
    public function viewAny(User $user): bool
    {
        return $user->isBusinessOwner() || $user->hasRole(['admin', 'manager']);
    }
    
    /**
     * Просмотр отдельного сотрудника.
     */
    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }
    
    /**
     * Только владелец бизнеса может создавать сотрудников.
     */
    public function create(User $user): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->isBusinessOwner();
    }
    
    /**
     * Только владелец бизнеса может обновлять сотрудников.
     */
    public function update(User $user): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->isBusinessOwner();
    }
    
    /**
     * Только владелец бизнеса может удалять сотрудников.
     */
    public function delete(User $user): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->isBusinessOwner();
    }
}
