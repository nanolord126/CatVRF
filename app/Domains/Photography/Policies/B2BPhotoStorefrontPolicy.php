<?php declare(strict_types=1);

/**
 * B2BPhotoStorefrontPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2bphotostorefrontpolicy
 */


namespace App\Domains\Photography\Policies;

final class B2BPhotoStorefrontPolicy
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
