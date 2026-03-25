declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\ClassSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * ClassPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ClassPolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, ClassSession $class): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_classes') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, ClassSession $class): Response
    {
        return ($user->id === $class->trainer->user_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, ClassSession $class): Response
    {
        return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }
}
