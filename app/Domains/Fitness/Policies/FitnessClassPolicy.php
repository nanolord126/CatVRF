declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Policies;

use App\Domains\Fitness\Models\FitnessClass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final /**
 * FitnessClassPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FitnessClassPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, FitnessClass $class): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_classes') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, FitnessClass $class): Response
    {
        return $user->hasPermissionTo('update_classes') ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, FitnessClass $class): Response
    {
        return $user->hasPermissionTo('delete_classes') ? $this->response->allow() : $this->response->deny();
    }
}
