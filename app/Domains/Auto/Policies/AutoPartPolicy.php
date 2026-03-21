<?php declare(strict_types=1);

namespace App\Domains\Auto\Policies;

use App\Models\User;
use App\Domains\Auto\Models\AutoPart;
use Illuminate\Auth\Access\Response;

/**
 * Policy для AutoPart.
 * Production 2026.
 */
final class AutoPartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, AutoPart $part): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): Response
    {
        if (!$user->isStaff()) {
            return Response::deny('Только персонал может создавать запчасти');
        }

        return Response::allow();
    }

    public function update(User $user, AutoPart $part): Response
    {
        if (!$user->isStaff()) {
            return Response::deny('Только персонал может редактировать запчасти');
        }

        return Response::allow();
    }

    public function delete(User $user, AutoPart $part): Response
    {
        if (!$user->isAdmin()) {
            return Response::deny('Только администратор может удалять запчасти');
        }

        return Response::allow();
    }
}
