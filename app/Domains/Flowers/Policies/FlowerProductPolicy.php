<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerProductPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, FlowerProduct $product): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            if ($user->isBusiness()) {
                return $this->response->allow();
            }

            return $this->response->deny('Only business users can create products');
        }

        public function update(User $user, FlowerProduct $product): Response
        {
            if ($user->id === $product->shop->user_id && $user->isBusiness()) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot update this product');
        }

        public function delete(User $user, FlowerProduct $product): Response
        {
            if ($user->id === $product->shop->user_id && $user->isBusiness()) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot delete this product');
        }
}
