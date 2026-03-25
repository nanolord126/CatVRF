declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use App\Domains\Pet\Models\PetBoardingReservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * PetBoardingPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PetBoardingPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, PetBoardingReservation $reservation): Response
    {
        return ($reservation->owner_id === $user->id || $reservation->clinic->owner_id === $user->id)
            && $reservation->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $this->response->allow();
    }

    public function update(User $user, PetBoardingReservation $reservation): Response
    {
        return $reservation->owner_id === $user->id && $reservation->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function cancel(User $user, PetBoardingReservation $reservation): Response
    {
        return ($reservation->owner_id === $user->id || $reservation->clinic->owner_id === $user->id)
            && $reservation->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function complete(User $user, PetBoardingReservation $reservation): Response
    {
        return $reservation->clinic->owner_id === $user->id && $reservation->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
