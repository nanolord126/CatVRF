<?php

declare(strict_types=1);


namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\PhotoStudio;
use Illuminate\Auth\Access\Response;

final /**
 * PhotoStudioPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PhotoStudioPolicy
{
	public function viewAny(User $user): Response
	{
		return $this->response->allow();
	}

	public function view(User $user, PhotoStudio $studio): Response
	{
		return $user->id === $studio->user_id || $user->is_admin
			? $this->response->allow()
			: $this->response->deny('Нет доступа');
	}

	public function create(User $user): Response
	{
		return $user->tenant_id ? $this->response->allow() : $this->response->deny('Требуется tenant');
	}

	public function update(User $user, PhotoStudio $studio): Response
	{
		return $user->id === $studio->user_id || $user->is_admin
			? $this->response->allow()
			: $this->response->deny('Нет доступа');
	}

	public function delete(User $user, PhotoStudio $studio): Response
	{
		return $user->id === $studio->user_id || $user->is_admin
			? $this->response->allow()
			: $this->response->deny('Нет доступа');
	}
}
