declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\FreelanceDeliverable;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FreelanceDeliverablePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FreelanceDeliverablePolicy
{
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
