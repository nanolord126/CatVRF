<?php

declare(strict_types=1);

namespace App\Domains\Food\Policies;

use App\Models\User;
use App\Domains\Food\Models\B2BFoodStorefront;
use App\Domains\Food\Models\B2BFoodOrder;
use Illuminate\Auth\Access\Response;

final class B2BFoodPolicy
{
	public function viewAny(User $user): Response { return Response::allow(); }

	public function viewStorefront(User $user, B2BFoodStorefront $storefront): Response
	{
		return $user->tenant_id === $storefront->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа');
	}

	public function createStorefront(User $user): Response
	{
		return $user->tenant_id && $user->has_verified_company ? Response::allow() : Response::deny('Требуется верификация');
	}

	public function updateStorefront(User $user, B2BFoodStorefront $storefront): Response
	{
		return $user->tenant_id === $storefront->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа');
	}

	public function viewOrder(User $user, B2BFoodOrder $order): Response
	{
		return $user->tenant_id === $order->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа');
	}

	public function approveOrder(User $user, B2BFoodOrder $order): Response
	{
		return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending' ? Response::allow() : Response::deny('Одобрение невозможно');
	}

	public function rejectOrder(User $user, B2BFoodOrder $order): Response
	{
		return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending' ? Response::allow() : Response::deny('Отклонение невозможно');
	}

	public function verifyInn(User $user): Response
	{
		return $user->is_admin ? Response::allow() : Response::deny('Только администратор');
	}
}
