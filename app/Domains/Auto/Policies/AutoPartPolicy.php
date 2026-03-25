declare(strict_types=1);

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
            return $this->response->deny('Только персонал может создавать запчасти');
        }

        return $this->response->allow();
    }

    public function update(User $user, AutoPart $part): Response
    {
        if (!$user->isStaff()) {
            return $this->response->deny('Только персонал может редактировать запчасти');
        }

        return $this->response->allow();
    }

    public function delete(User $user, AutoPart $part): Response
    {
        if (!$user->isAdmin()) {
            return $this->response->deny('Только администратор может удалять запчасти');
        }

        return $this->response->allow();
    }
}
