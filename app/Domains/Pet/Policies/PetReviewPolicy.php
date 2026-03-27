<?php

declare(strict_types=1);


namespace App\Domains\Pet\Policies;

use App\Domains\Pet\Models\PetReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * PetReviewPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PetReviewPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, PetReview $review): Response
    {
        return $review->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $this->response->allow();
    }

    public function update(User $user, PetReview $review): Response
    {
        return $review->reviewer_id === $user->id && $review->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, PetReview $review): Response
    {
        return $review->reviewer_id === $user->id && $review->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function approve(User $user, PetReview $review): Response
    {
        return $user->hasPermissionTo('pet_review_approve')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
