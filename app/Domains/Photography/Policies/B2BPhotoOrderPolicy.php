<?php

declare(strict_types=1);

namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\B2BPhotoOrder;
use Illuminate\Auth\Access\Response;

final class B2BPhotoOrderPolicy
{
	public function viewAny(User $user): Response
	{
		return Response::allow();
	}

	public function view(User $user, B2BPhotoOrder $order): Response
	{
		return $user->tenant_id === $order->tenant_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}

	public function approve(User $user, B2BPhotoOrder $order): Response
	{
		return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending'
			? Response::allow()
			: Response::deny('Одобрение невозможно');
	}

	public function reject(User $user, B2BPhotoOrder $order): Response
	{
		return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending'
			? Response::allow()
			: Response::deny('Отклонение невозможно');
	}
}
