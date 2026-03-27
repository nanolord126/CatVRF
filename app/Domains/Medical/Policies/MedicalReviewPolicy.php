<?php

declare(strict_types=1);


namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * MedicalReviewPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MedicalReviewPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, MedicalReview $review): Response
    {
        return $review->status === 'approved' || $user->id === $review->reviewer_id || $user->hasRole('admin')
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function create(User $user): Response
    {
        return $this->response->allow();
    }

    public function update(User $user, MedicalReview $review): Response
    {
        return $user->id === $review->reviewer_id || $user->hasRole('admin')
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function delete(User $user, MedicalReview $review): Response
    {
        return $user->id === $review->reviewer_id || $user->hasRole('admin')
            ? $this->response->allow()
            : $this->response->deny();
    }
}
