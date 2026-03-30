<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentRatingPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, ShipmentRating $rating): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, ShipmentRating $rating): Response
        {
            return $user->id === $rating->reviewer_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, ShipmentRating $rating): Response
        {
            return $user->id === $rating->reviewer_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }
}
