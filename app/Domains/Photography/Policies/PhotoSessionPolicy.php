<?php

declare(strict_types=1);

namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Auth\Access\Response;

final class PhotoSessionPolicy
{
	public function viewAny(User $user): Response
	{
		return Response::allow();
	}

	public function view(User $user, PhotoSession $session): Response
	{
		return $user->id === $session->user_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}

	public function create(User $user): Response
	{
		return $user->tenant_id ? Response::allow() : Response::deny('Требуется tenant');
	}

	public function cancel(User $user, PhotoSession $session): Response
	{
		return ($user->id === $session->user_id || $user->is_admin) && in_array($session->status, ['pending', 'confirmed'])
			? Response::allow()
			: Response::deny('Отмена невозможна');
	}
}
