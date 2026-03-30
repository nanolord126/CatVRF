<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): bool
        {
            return $user->can('view_events');
        }

        public function view(User $user, Event $event): bool
        {
            return $user->tenant_id === $event->tenant_id;
        }

        public function create(User $user): bool
        {
            return $user->can('manage_entertainment');
        }

        public function update(User $user, Event $event): bool
        {
            return $user->tenant_id === $event->tenant_id && $user->can('manage_entertainment');
        }

        public function delete(User $user, Event $event): bool
        {
            return $user->tenant_id === $event->tenant_id && $user->hasRole('admin');
        }
}
