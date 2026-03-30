<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceDisputePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
