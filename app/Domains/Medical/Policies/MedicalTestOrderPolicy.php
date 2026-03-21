<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalTestOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class MedicalTestOrderPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_test_orders') ? Response::allow() : Response::deny();
    }

    public function view(User $user, MedicalTestOrder $testOrder): Response
    {
        return $user->id === $testOrder->patient_id || $user->hasRole('admin')
            ? Response::allow()
            : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_test_order') ? Response::allow() : Response::deny();
    }

    public function update(User $user, MedicalTestOrder $testOrder): Response
    {
        return $user->hasRole('admin') ? Response::allow() : Response::deny();
    }

    public function delete(User $user, MedicalTestOrder $testOrder): Response
    {
        return $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
