declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\Studio;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * StudioPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class StudioPolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, Studio $studio): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_studios') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, Studio $studio): Response
    {
        return ($user->id === $studio->owner_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, Studio $studio): Response
    {
        return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }
}
