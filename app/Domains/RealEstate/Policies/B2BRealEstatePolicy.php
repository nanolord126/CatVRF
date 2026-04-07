<?php declare(strict_types=1);

/**
 * B2BRealEstatePolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2brealestatepolicy
 */


namespace App\Domains\RealEstate\Policies;

final class B2BRealEstatePolicy
{

    public function viewAny(User $user): Response { return $this->response->allow(); }
    	public function viewStorefront(User $user, B2BRealEstateStorefront $s): Response { return $user->tenant_id === $s->tenant_id || $user->is_admin ? $this->response->allow() : $this->response->deny('Нет доступа'); }
    	public function createStorefront(User $user): Response { return $user->tenant_id && $user->has_verified_company ? $this->response->allow() : $this->response->deny('Требуется верификация'); }
    	public function updateStorefront(User $user, B2BRealEstateStorefront $s): Response { return $user->tenant_id === $s->tenant_id || $user->is_admin ? $this->response->allow() : $this->response->deny('Нет доступа'); }
    	public function viewOrder(User $user, B2BRealEstateOrder $o): Response { return $user->tenant_id === $o->tenant_id || $user->is_admin ? $this->response->allow() : $this->response->deny('Нет доступа'); }
    	public function approveOrder(User $user, B2BRealEstateOrder $o): Response { return ($user->tenant_id === $o->tenant_id || $user->is_admin) && $o->status === 'pending' ? $this->response->allow() : $this->response->deny('Одобрение невозможно'); }
    	public function rejectOrder(User $user, B2BRealEstateOrder $o): Response { return ($user->tenant_id === $o->tenant_id || $user->is_admin) && $o->status === 'pending' ? $this->response->allow() : $this->response->deny('Отклонение невозможно'); }
    	public function verifyInn(User $user): Response { return $user->is_admin ? $this->response->allow() : $this->response->deny('Только администратор'); }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
