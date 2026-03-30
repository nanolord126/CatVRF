<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VenuePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): bool
        {
            return $user->can('view_venues');
        }

        public function view(User $user, Venue $venue): bool
        {
            return $user->tenant_id === $venue->tenant_id;
        }

        public function create(User $user): bool
        {
            return $user->can('manage_entertainment');
        }

        public function update(User $user, Venue $venue): bool
        {
            return $user->tenant_id === $venue->tenant_id && $user->can('manage_entertainment');
        }

        public function delete(User $user, Venue $venue): bool
        {
            return $user->tenant_id === $venue->tenant_id && $user->hasRole('admin');
        }
}
