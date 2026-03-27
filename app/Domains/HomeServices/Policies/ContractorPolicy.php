<?php

declare(strict_types=1);


namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\Contractor;
use Illuminate\Auth\Access\Response;

final /**
 * ContractorPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ContractorPolicy
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, Contractor $contractor): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_contractors') ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function update(User $user, Contractor $contractor): Response
    {
        return $user->id === $contractor->user_id || $user->hasPermissionTo('update_contractors') ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, Contractor $contractor): Response
    {
        return $user->hasPermissionTo('delete_contractors') ? $this->response->allow() : $this->response->deny('Unauthorized');
    }
}
