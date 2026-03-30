<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetVetPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, PetVet $vet): Response
        {
            return $vet->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('pet_vet_create')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function update(User $user, PetVet $vet): Response
        {
            return ($vet->clinic->owner_id === $user->id || $vet->user_id === $user->id)
                && $vet->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function delete(User $user, PetVet $vet): Response
        {
            return $vet->clinic->owner_id === $user->id && $vet->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }
}
