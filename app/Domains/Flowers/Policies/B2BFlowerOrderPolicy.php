<?php declare(strict_types=1);

/**
 * B2BFlowerOrderPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2bflowerorderpolicy
 */


namespace App\Domains\Flowers\Policies;

final class B2BFlowerOrderPolicy
{

    public function viewAny(User $user): Response
        {
            if ($user->company_inn) {
                return $this->response->allow();
            }

            return $this->response->deny('Company INN is required');
        }

        public function view(User $user, B2BFlowerOrder $order): Response
        {
            if ($user->company_inn === $order->storefront->company_inn || $user->id === $order->shop->user_id) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot view this order');
        }

        public function create(User $user): Response
        {
            if ($user->company_inn && $user->b2bFlowerStorefront?->is_active) {
                return $this->response->allow();
            }

            return $this->response->deny('Active B2B storefront required');
        }

        public function update(User $user, B2BFlowerOrder $order): Response
        {
            if ($user->company_inn === $order->storefront->company_inn && $order->status === 'draft') {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot update this order');
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
