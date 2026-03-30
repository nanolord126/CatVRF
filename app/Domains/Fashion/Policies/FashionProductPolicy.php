<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionProductPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, FashionProduct $product): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermission('create_product') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FashionProduct $product): Response
        {
            return $user->id === $product->store->owner_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, FashionProduct $product): Response
        {
            return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }
}
