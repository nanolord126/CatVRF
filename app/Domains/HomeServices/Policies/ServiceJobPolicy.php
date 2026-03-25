declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\ServiceJob;
use Illuminate\Auth\Access\Response;

final /**
 * ServiceJobPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ServiceJobPolicy
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function viewAny(User $user): Response
    {
        return $user->auth() ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function view(User $user, ServiceJob $job): Response
    {
        return $user->id === $job->client_id || $user->id === $job->contractor->user_id ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->auth() ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function accept(User $user, ServiceJob $job): Response
    {
        return $user->id === $job->contractor->user_id ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function cancel(User $user, ServiceJob $job): Response
    {
        return $user->id === $job->client_id || $user->id === $job->contractor->user_id ? $this->response->allow() : $this->response->deny('Unauthorized');
    }
}
