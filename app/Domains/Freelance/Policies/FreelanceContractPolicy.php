<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceContractPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function view(User $user, FreelanceContract $contract): Response
        {
            return $user->id === $contract->freelancer->user_id || $user->id === $contract->client_id
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function update(User $user, FreelanceContract $contract): Response
        {
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function release(User $user, FreelanceContract $contract): Response
        {
            return $user->id === $contract->client_id && in_array($contract->status, ['active', 'on_hold'])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function complete(User $user, FreelanceContract $contract): Response
        {
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function pause(User $user, FreelanceContract $contract): Response
        {
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function cancel(User $user, FreelanceContract $contract): Response
        {
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }
}
