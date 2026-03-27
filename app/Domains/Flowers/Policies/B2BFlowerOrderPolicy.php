<?php

declare(strict_types=1);


namespace App\Domains\Flowers\Policies;

use App\Domains\Flowers\Models\B2BFlowerOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * B2BFlowerOrderPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BFlowerOrderPolicy
{
    public function viewAny(User $user): Response
    {
        if ($user->company_inn) {
            return $this->response->allow();
        }

        return $this->response->deny('Company INN is required');
    }

    public function view(User $user, B2BFlowerOrder $order): Response
    {
        if ($user->company_inn === $order->storefront->company_inn || $user->id === $order->shop->user_id) {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot view this order');
    }

    public function create(User $user): Response
    {
        if ($user->company_inn && $user->b2bFlowerStorefront?->is_active) {
            return $this->response->allow();
        }

        return $this->response->deny('Active B2B storefront required');
    }

    public function update(User $user, B2BFlowerOrder $order): Response
    {
        if ($user->company_inn === $order->storefront->company_inn && $order->status === 'draft') {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot update this order');
    }
}
