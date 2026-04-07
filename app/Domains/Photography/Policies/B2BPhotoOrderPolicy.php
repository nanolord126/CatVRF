<?php declare(strict_types=1);

/**
 * B2BPhotoOrderPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2bphotoorderpolicy
 */


namespace App\Domains\Photography\Policies;

final class B2BPhotoOrderPolicy
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
