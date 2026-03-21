<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use App\Domains\Pet\Models\PetVet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class PetVetPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, PetVet $vet): Response
    {
        return $vet->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('pet_vet_create')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, PetVet $vet): Response
    {
        return ($vet->clinic->owner_id === $user->id || $vet->user_id === $user->id)
            && $vet->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function delete(User $user, PetVet $vet): Response
    {
        return $vet->clinic->owner_id === $user->id && $vet->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
