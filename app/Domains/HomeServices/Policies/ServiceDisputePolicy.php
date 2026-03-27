<?php

declare(strict_types=1);


namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\ServiceDispute;
use Illuminate\Auth\Access\Response;

final /**
 * ServiceDisputePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ServiceDisputePolicy
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function viewAny(User $user): Response
    {
        return $user->auth() ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function view(User $user, ServiceDispute $dispute): Response
    {
        return $user->id === $dispute->initiator_id || $user->id === $dispute->job->contractor->user_id || $user->hasPermissionTo('view_disputes') ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->auth() ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function resolve(User $user, ServiceDispute $dispute): Response
    {
        return $user->hasPermissionTo('resolve_disputes') ? $this->response->allow() : $this->response->deny('Unauthorized');
    }
}
