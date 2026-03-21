<?php

declare(strict_types=1);

namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\B2BPhotoStorefront;
use Illuminate\Auth\Access\Response;

final class B2BPhotoStorefrontPolicy
{
	public function viewAny(User $user): Response
	{
		return Response::allow();
	}

	public function view(User $user, B2BPhotoStorefront $storefront): Response
	{
		return $user->tenant_id === $storefront->tenant_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}

	public function create(User $user): Response
	{
		return $user->tenant_id && $user->has_verified_company
			? Response::allow()
			: Response::deny('Требуется верификация компании');
	}

	public function update(User $user, B2BPhotoStorefront $storefront): Response
	{
		return $user->tenant_id === $storefront->tenant_id || $user->is_admin
			? Response::allow()
			: Response::deny('Нет доступа');
	}
}
