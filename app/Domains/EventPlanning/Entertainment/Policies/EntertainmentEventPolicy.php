<?php

declare(strict_types=1);


namespace App\Domains\EventPlanning\Entertainment\Policies;

use App\Models\User;
use App\Domains\EventPlanning\Entertainment\Models\EntertainmentEvent;
use Illuminate\Auth\Access\Response;

final /**
 * EntertainmentEventPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EntertainmentEventPolicy
{
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
