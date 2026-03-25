declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\ShipmentRating;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * ShipmentRatingPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentRatingPolicy
{
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
