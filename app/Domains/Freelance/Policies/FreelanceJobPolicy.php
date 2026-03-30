<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceJobPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function view(?User $user, FreelanceJob $job): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->id ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FreelanceJob $job): Response
        {
            return $user->id === $job->client_id ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, FreelanceJob $job): Response
        {
            return $user->id === $job->client_id ? $this->response->allow() : $this->response->deny();
        }

        public function viewProposals(User $user, FreelanceJob $job): Response
        {
            return $user->id === $job->client_id ? $this->response->allow() : $this->response->deny();
        }

        public function close(User $user, FreelanceJob $job): Response
        {
            return $user->id === $job->client_id ? $this->response->allow() : $this->response->deny();
        }
}
