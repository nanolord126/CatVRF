<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use App\Models\User;
use App\Domains\Hotels\Models\B2BHotelStorefront;
use App\Domains\Hotels\Models\B2BHotelOrder;
use Illuminate\Auth\Access\Response;

final class B2BHotelPolicy
{
	public function viewAny(User $user): Response { return Response::allow(); }
	public function viewStorefront(User $user, B2BHotelStorefront $s): Response { return $user->tenant_id === $s->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа'); }
	public function createStorefront(User $user): Response { return $user->tenant_id && $user->has_verified_company ? Response::allow() : Response::deny('Требуется верификация'); }
	public function updateStorefront(User $user, B2BHotelStorefront $s): Response { return $user->tenant_id === $s->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа'); }
	public function viewOrder(User $user, B2BHotelOrder $o): Response { return $user->tenant_id === $o->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа'); }
	public function approveOrder(User $user, B2BHotelOrder $o): Response { return ($user->tenant_id === $o->tenant_id || $user->is_admin) && $o->status === 'pending' ? Response::allow() : Response::deny('Одобрение невозможно'); }
	public function rejectOrder(User $user, B2BHotelOrder $o): Response { return ($user->tenant_id === $o->tenant_id || $user->is_admin) && $o->status === 'pending' ? Response::allow() : Response::deny('Отклонение невозможно'); }
	public function verifyInn(User $user): Response { return $user->is_admin ? Response::allow() : Response::deny('Только администратор'); }
}
