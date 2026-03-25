declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionStore;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FashionStorePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FashionStorePolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, FashionStore $store): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('create_fashion_store') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, FashionStore $store): Response
    {
        return $user->id === $store->owner_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, FashionStore $store): Response
    {
        return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }
}
