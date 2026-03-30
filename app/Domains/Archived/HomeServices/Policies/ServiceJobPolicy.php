<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceJobPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
