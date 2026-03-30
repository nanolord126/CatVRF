<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelancerPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
