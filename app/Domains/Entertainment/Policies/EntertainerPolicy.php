declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Policies;

use App\Models\User;
use App\Domains\Entertainment\Models\Entertainer;
use Illuminate\Auth\Access\Response;

final /**
 * EntertainerPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EntertainerPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, Entertainer $entertainer): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_entertainers')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, Entertainer $entertainer): Response
    {
        return $user->id === $entertainer->user_id || $user->hasPermissionTo('update_entertainers')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, Entertainer $entertainer): Response
    {
        return $user->hasPermissionTo('delete_entertainers')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
