declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionProduct;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FashionProductPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FashionProductPolicy
{
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
