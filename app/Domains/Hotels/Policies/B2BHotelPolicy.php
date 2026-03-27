<?php

declare(strict_types=1);


namespace App\Domains\Hotels\Policies;

use App\Models\User;
use App\Domains\Hotels\Models\B2BHotelStorefront;
use App\Domains\Hotels\Models\B2BHotelOrder;
use Illuminate\Auth\Access\Response;

final /**
 * B2BHotelPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BHotelPolicy
{
	public function viewAny(User $user): Response { return $this->response->allow(); }
	public function viewStorefront(User $user, B2BHotelStorefront $s): Response { return $user->tenant_id === $s->tenant_id || $user->is_admin ? $this->response->allow() : $this->response->deny('Нет доступа'); }
	public function createStorefront(User $user): Response { return $user->tenant_id && $user->has_verified_company ? $this->response->allow() : $this->response->deny('Требуется верификация'); }
	public function updateStorefront(User $user, B2BHotelStorefront $s): Response { return $user->tenant_id === $s->tenant_id || $user->is_admin ? $this->response->allow() : $this->response->deny('Нет доступа'); }
	public function viewOrder(User $user, B2BHotelOrder $o): Response { return $user->tenant_id === $o->tenant_id || $user->is_admin ? $this->response->allow() : $this->response->deny('Нет доступа'); }
	public function approveOrder(User $user, B2BHotelOrder $o): Response { return ($user->tenant_id === $o->tenant_id || $user->is_admin) && $o->status === 'pending' ? $this->response->allow() : $this->response->deny('Одобрение невозможно'); }
	public function rejectOrder(User $user, B2BHotelOrder $o): Response { return ($user->tenant_id === $o->tenant_id || $user->is_admin) && $o->status === 'pending' ? $this->response->allow() : $this->response->deny('Отклонение невозможно'); }
	public function verifyInn(User $user): Response { return $user->is_admin ? $this->response->allow() : $this->response->deny('Только администратор'); }
}
