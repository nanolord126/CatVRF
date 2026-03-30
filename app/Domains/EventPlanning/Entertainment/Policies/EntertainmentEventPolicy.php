<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EntertainmentEventPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, EntertainmentEvent $event): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_entertainment_events')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function update(User $user, EntertainmentEvent $event): Response
        {
            return $user->hasPermissionTo('update_entertainment_events')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function delete(User $user, EntertainmentEvent $event): Response
        {
            return $user->hasPermissionTo('delete_entertainment_events')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }
}
