<?php

declare(strict_types=1);


namespace App\Domains\Hotels\Policies;

use App\Domains\Hotels\Models\RoomType;
use Illuminate\Auth\Access\Response;

final /**
 * RoomTypePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class RoomTypePolicy
{
    public function viewAny(): Response
    {
        return $this->response->allow();
    }

    public function view(): Response
    {
        return $this->response->allow();
    }

    public function create(): Response
    {
        return auth()->check() && auth()->user()->can('create_room_types')
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }

    public function update(): Response
    {
        return auth()->check() && (auth()->user()->is_admin || auth()->user()->is_hotel_owner)
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }

    public function delete(): Response
    {
        return auth()->check() && auth()->user()->is_admin
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }
}
