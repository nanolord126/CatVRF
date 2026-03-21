<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\ClassSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class ClassPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, ClassSession $class): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_classes') ? Response::allow() : Response::deny();
    }

    public function update(User $user, ClassSession $class): Response
    {
        return ($user->id === $class->trainer->user_id || $user->hasRole('admin')) ? Response::allow() : Response::deny();
    }

    public function delete(User $user, ClassSession $class): Response
    {
        return $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
