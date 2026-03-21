<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

use App\Models\User;
use App\Domains\RealEstate\Models\B2BRealEstateStorefront;
use App\Domains\RealEstate\Models\B2BRealEstateOrder;
use Illuminate\Auth\Access\Response;

final class B2BRealEstatePolicy
{
	public function viewAny(User $user): Response { return Response::allow(); }
	public function viewStorefront(User $user, B2BRealEstateStorefront $s): Response { return $user->tenant_id === $s->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа'); }
	public function createStorefront(User $user): Response { return $user->tenant_id && $user->has_verified_company ? Response::allow() : Response::deny('Требуется верификация'); }
	public function updateStorefront(User $user, B2BRealEstateStorefront $s): Response { return $user->tenant_id === $s->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа'); }
	public function viewOrder(User $user, B2BRealEstateOrder $o): Response { return $user->tenant_id === $o->tenant_id || $user->is_admin ? Response::allow() : Response::deny('Нет доступа'); }
	public function approveOrder(User $user, B2BRealEstateOrder $o): Response { return ($user->tenant_id === $o->tenant_id || $user->is_admin) && $o->status === 'pending' ? Response::allow() : Response::deny('Одобрение невозможно'); }
	public function rejectOrder(User $user, B2BRealEstateOrder $o): Response { return ($user->tenant_id === $o->tenant_id || $user->is_admin) && $o->status === 'pending' ? Response::allow() : Response::deny('Отклонение невозможно'); }
	public function verifyInn(User $user): Response { return $user->is_admin ? Response::allow() : Response::deny('Только администратор'); }
}
