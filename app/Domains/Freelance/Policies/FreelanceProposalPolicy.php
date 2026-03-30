<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceProposalPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function view(User $user, FreelanceProposal $proposal): Response
        {
            return $user->id === $proposal->freelancer->user_id || $user->id === $proposal->job->client_id
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->id ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FreelanceProposal $proposal): Response
        {
            return $user->id === $proposal->freelancer->user_id && $proposal->status === 'pending'
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function delete(User $user, FreelanceProposal $proposal): Response
        {
            return $user->id === $proposal->freelancer->user_id && in_array($proposal->status, ['pending', 'rejected'])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function accept(User $user, FreelanceProposal $proposal): Response
        {
            return $user->id === $proposal->job->client_id && $proposal->status === 'pending'
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function reject(User $user, FreelanceProposal $proposal): Response
        {
            return $user->id === $proposal->job->client_id && $proposal->status === 'pending'
                ? $this->response->allow()
                : $this->response->deny();
        }
}
