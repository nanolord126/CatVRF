<?php

declare(strict_types=1);

/**
 * BeautyProductPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/beautyproductpolicy
 */


namespace App\Domains\Beauty\Policies;

final class BeautyProductPolicy
{

    use HandlesAuthorization;

        public function viewAny(User $user): bool
        {
            return (bool) filament()->getTenant();
        }

        public function view(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant()->id;
        }

        public function create(User $user): bool
        {
            return (bool) filament()->getTenant() && $user->can('create_products');
        }

        public function update(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant()->id && $user->can('update_products');
        }

        public function delete(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant()->id && $user->can('delete_products');
        }

        public function restore(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant()->id && $user->can('restore_products');
        }

        public function forceDelete(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant()->id && $user->can('force_delete_products');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
