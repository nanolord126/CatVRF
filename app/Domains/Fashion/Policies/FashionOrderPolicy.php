<?php

declare(strict_types=1);


namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FashionOrderPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FashionOrderPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('view_orders') ? $this->response->allow() : $this->response->deny();
    }

    public function view(User $user, FashionOrder $order): Response
    {
        return $user->id === $order->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('create_order') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, FashionOrder $order): Response
    {
        return $user->id === $order->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, FashionOrder $order): Response
    {
        return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }
}
