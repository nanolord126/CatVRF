<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceReviewPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here


        public function viewAny(User $user): Response


        {


            return $this->response->allow();


        }


        public function view(User $user, ServiceReview $review): Response


        {


            return $this->response->allow();


        }


        public function create(User $user): Response


        {


            return $user->auth() ? $this->response->allow() : $this->response->deny('Unauthorized');


        }


        public function update(User $user, ServiceReview $review): Response


        {


            return $user->id === $review->reviewer_id || $user->hasPermissionTo('update_reviews') ? $this->response->allow() : $this->response->deny('Unauthorized');


        }


        public function delete(User $user, ServiceReview $review): Response


        {


            return $user->id === $review->reviewer_id || $user->hasPermissionTo('delete_reviews') ? $this->response->allow() : $this->response->deny('Unauthorized');


        }
}
