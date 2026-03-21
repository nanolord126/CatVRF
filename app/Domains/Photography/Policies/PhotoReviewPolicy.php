<?php

declare(strict_types=1);

namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\PhotoReview;
use Illuminate\Auth\Access\Response;

final class PhotoReviewPolicy
{
	public function viewAny(User $user): Response
	{
		return Response::allow();
	}

	public function create(User $user): Response
	{
		return $user->tenant_id ? Response::allow() : Response::deny('Требуется tenant');
	}

	public function update(User $user, PhotoReview $review): Response
	{
		return $user->id === $review->user_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}

	public function delete(User $user, PhotoReview $review): Response
	{
		return $user->id === $review->user_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}
}
