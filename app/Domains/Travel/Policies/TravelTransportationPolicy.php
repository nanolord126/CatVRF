<?php declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelTransportationPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, TravelTransportation $transportation): Response
        {
            if ($transportation->tenant_id !== tenant()->id) {
                return $this->response->deny('Unauthorized');
            }

            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->can('create_travel_transportation')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function update(User $user, TravelTransportation $transportation): Response
        {
            if ($transportation->tenant_id !== tenant()->id) {
                return $this->response->deny('Unauthorized');
            }

            if ($transportation->agency && $transportation->agency->owner_id !== $user->id && !$user->can('update_travel_transportation')) {
                return $this->response->deny('Unauthorized');
            }

            return $this->response->allow();
        }

        public function delete(User $user, TravelTransportation $transportation): Response
        {
            if ($transportation->tenant_id !== tenant()->id) {
                return $this->response->deny('Unauthorized');
            }

            if ($transportation->agency && $transportation->agency->owner_id !== $user->id && !$user->can('delete_travel_transportation')) {
                return $this->response->deny('Unauthorized');
            }

            return $this->response->allow();
        }

        public function restore(User $user, TravelTransportation $transportation): Response
        {
            if ($transportation->tenant_id !== tenant()->id) {
                return $this->response->deny('Unauthorized');
            }

            return $user->can('restore_travel_transportation')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function forceDelete(User $user, TravelTransportation $transportation): Response
        {
            if ($transportation->tenant_id !== tenant()->id) {
                return $this->response->deny('Unauthorized');
            }

            return $user->can('force_delete_travel_transportation')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }
}
