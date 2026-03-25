declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Photography\Policies;

use App\Models\User;
use App\Domains\Photography\Models\B2BPhotoOrder;
use Illuminate\Auth\Access\Response;

final /**
 * B2BPhotoOrderPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BPhotoOrderPolicy
{
	public function viewAny(User $user): Response
	{
		return $this->response->allow();
	}

	public function view(User $user, B2BPhotoOrder $order): Response
	{
		return $user->tenant_id === $order->tenant_id || $user->is_admin
			? $this->response->allow()
			: $this->response->deny('Нет доступа');
	}

	public function approve(User $user, B2BPhotoOrder $order): Response
	{
		return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending'
			? $this->response->allow()
			: $this->response->deny('Одобрение невозможно');
	}

	public function reject(User $user, B2BPhotoOrder $order): Response
	{
		return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending'
			? $this->response->allow()
			: $this->response->deny('Отклонение невозможно');
	}
}
