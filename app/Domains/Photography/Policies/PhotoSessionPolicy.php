<?php

declare(strict_types=1);


namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Auth\Access\Response;

final /**
 * PhotoSessionPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PhotoSessionPolicy
{
	public function viewAny(User $user): Response
	{
		return $this->response->allow();
	}

	public function view(User $user, PhotoSession $session): Response
	{
		return $user->id === $session->user_id || $user->is_admin
			? $this->response->allow()
			: $this->response->deny('Нет доступа');
	}

	public function create(User $user): Response
	{
		return $user->tenant_id ? $this->response->allow() : $this->response->deny('Требуется tenant');
	}

	public function cancel(User $user, PhotoSession $session): Response
	{
		return ($user->id === $session->user_id || $user->is_admin) && in_array($session->status, ['pending', 'confirmed'])
			? $this->response->allow()
			: $this->response->deny('Отмена невозможна');
	}
}
