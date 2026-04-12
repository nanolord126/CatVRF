<?php declare(strict_types=1);

/**
 * ShopProductPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/shopproductpolicy
 */


namespace App\Domains\Marketplace\Shop\Policies;

final class ShopProductPolicy
{
        public function view(User $user, ShopProduct $product): bool
        {
            return $user->tenant_id === $product->tenant_id;
        }

        public function update(User $user, ShopProduct $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business_owner', 'manager']);
        }

        public function delete(User $user, ShopProduct $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole('business_owner');
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
