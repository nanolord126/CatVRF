declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\Freelancer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FreelancerPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FreelancerPolicy
{
    public function view(?User $user, Freelancer $freelancer): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->id ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, Freelancer $freelancer): Response
    {
        return $user->id === $freelancer->user_id ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, Freelancer $freelancer): Response
    {
        return $user->id === $freelancer->user_id ? $this->response->allow() : $this->response->deny();
    }

    public function viewDetails(User $user, Freelancer $freelancer): Response
    {
        return $this->response->allow();
    }
}
