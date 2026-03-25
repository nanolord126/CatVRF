declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\FreelanceProposal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FreelanceProposalPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FreelanceProposalPolicy
{
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
