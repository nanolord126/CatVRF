<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ContractorPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
