declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use App\Domains\Pet\Models\PetClinic;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * PetClinicPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PetClinicPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, PetClinic $clinic): Response
    {
        return $clinic->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('pet_clinic_create')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, PetClinic $clinic): Response
    {
        return $clinic->owner_id === $user->id && $clinic->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, PetClinic $clinic): Response
    {
        return $clinic->owner_id === $user->id && $clinic->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function verify(User $user, PetClinic $clinic): Response
    {
        return $user->hasPermissionTo('pet_clinic_verify')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
