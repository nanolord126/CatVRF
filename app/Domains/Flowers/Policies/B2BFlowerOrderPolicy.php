<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BFlowerOrderPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
