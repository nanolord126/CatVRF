<?php

declare(strict_types=1);

namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\PhotoStudio;
use Illuminate\Auth\Access\Response;

final class PhotoStudioPolicy
{
	public function viewAny(User $user): Response
	{
		return Response::allow();
	}

	public function view(User $user, PhotoStudio $studio): Response
	{
		return $user->id === $studio->user_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}

	public function create(User $user): Response
	{
		return $user->tenant_id ? Response::allow() : Response::deny('Требуется tenant');
	}

	public function update(User $user, PhotoStudio $studio): Response
	{
		return $user->id === $studio->user_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}

	public function delete(User $user, PhotoStudio $studio): Response
	{
		return $user->id === $studio->user_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}
}
