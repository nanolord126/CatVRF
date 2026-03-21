<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

final class B2BPetPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->is_business
            ? Response::allow()
            : Response::deny('Только для бизнеса');
    }

    public function viewStorefront(User $user): Response
    {
        return $user->is_business
            ? Response::allow()
            : Response::deny('Только для бизнеса');
    }

    public function createStorefront(User $user): Response
    {
        return $user->is_business && $user->is_verified
            ? Response::allow()
            : Response::deny('Требуется верификация');
    }

    public function updateStorefront(User $user): Response
    {
        return $user->is_business
            ? Response::allow()
            : Response::deny('Только для бизнеса');
    }

    public function viewOrder(User $user): Response
    {
        return $user->is_business
            ? Response::allow()
            : Response::deny('Только для бизнеса');
    }

    public function approveOrder(User $user): Response
    {
        return $user->is_business && $user->is_verified
            ? Response::allow()
            : Response::deny('Требуется верификация');
    }

    public function rejectOrder(User $user): Response
    {
        return $user->is_business
            ? Response::allow()
            : Response::deny('Только для бизнеса');
    }

    public function verifyInn(User $user): Response
    {
        return $user->is_admin
            ? Response::allow()
            : Response::deny('Только администратор');
    }
}
