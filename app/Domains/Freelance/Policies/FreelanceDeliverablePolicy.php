<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceDeliverablePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function view(User $user, FreelanceDeliverable $deliverable): Response
        {
            $contract = $deliverable->contract;
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->id ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FreelanceDeliverable $deliverable): Response
        {
            return $user->id === $deliverable->freelancer->user_id && $deliverable->status === 'pending'
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function approve(User $user, FreelanceDeliverable $deliverable): Response
        {
            return $user->id === $deliverable->contract->client_id && $deliverable->status === 'submitted'
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function requestRevision(User $user, FreelanceDeliverable $deliverable): Response
        {
            return $user->id === $deliverable->contract->client_id && in_array($deliverable->status, ['submitted', 'pending'])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function reject(User $user, FreelanceDeliverable $deliverable): Response
        {
            return $user->id === $deliverable->contract->client_id && in_array($deliverable->status, ['submitted', 'revisions_requested'])
                ? $this->response->allow()
                : $this->response->deny();
        }
}
