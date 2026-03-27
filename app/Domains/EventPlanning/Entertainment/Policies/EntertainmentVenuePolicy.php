<?php

declare(strict_types=1);


namespace App\Domains\EventPlanning\Entertainment\Policies;

use App\Models\User;
use App\Domains\EventPlanning\Entertainment\Models\EntertainmentVenue;
use Illuminate\Auth\Access\Response;

final /**
 * EntertainmentVenuePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EntertainmentVenuePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, EntertainmentVenue $venue): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_entertainment_venues')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, EntertainmentVenue $venue): Response
    {
        return $user->hasPermissionTo('update_entertainment_venues')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, EntertainmentVenue $venue): Response
    {
        return $user->hasPermissionTo('delete_entertainment_venues')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
