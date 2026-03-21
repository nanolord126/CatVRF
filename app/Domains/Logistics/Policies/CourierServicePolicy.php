<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\CourierService;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class CourierServicePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, CourierService $courierService): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_courier_service') ? Response::allow() : Response::deny();
    }

    public function update(User $user, CourierService $courierService): Response
    {
        return $user->id === $courierService->user_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }

    public function delete(User $user, CourierService $courierService): Response
    {
        return $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
