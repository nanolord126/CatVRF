<?php

declare(strict_types=1);


namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\FreelanceJob;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FreelanceJobPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FreelanceJobPolicy
{
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
