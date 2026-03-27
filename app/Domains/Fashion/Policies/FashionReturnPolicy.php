<?php

declare(strict_types=1);


namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionReturn;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FashionReturnPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FashionReturnPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('view_returns') ? $this->response->allow() : $this->response->deny();
    }

    public function view(User $user, FashionReturn $return): Response
    {
        return $user->id === $return->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('request_return') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, FashionReturn $return): Response
    {
        return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, FashionReturn $return): Response
    {
        return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }
}
