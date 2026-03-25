declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\B2BPhotoStorefront;
use Illuminate\Auth\Access\Response;

final /**
 * B2BPhotoStorefrontPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BPhotoStorefrontPolicy
{
	public function viewAny(User $user): Response
	{
		return $this->response->allow();
	}

	public function view(User $user, B2BPhotoStorefront $storefront): Response
	{
		return $user->tenant_id === $storefront->tenant_id || $user->is_admin
			? $this->response->allow()
			: $this->response->deny('Нет доступа');
	}

	public function create(User $user): Response
	{
		return $user->tenant_id && $user->has_verified_company
			? $this->response->allow()
			: $this->response->deny('Требуется верификация компании');
	}

	public function update(User $user, B2BPhotoStorefront $storefront): Response
	{
		return $user->tenant_id === $storefront->tenant_id || $user->is_admin
			? $this->response->allow()
			: $this->response->deny('Нет доступа');
	}
}
