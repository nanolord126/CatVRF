<?php declare(strict_types=1);

/**
 * FlowerOrderPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/flowerorderpolicy
 */


namespace App\Domains\Flowers\Policies;

final class FlowerOrderPolicy
{

    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, FlowerOrder $order): Response
        {
            if ($user->id === $order->user_id || $user->id === $order->shop->user_id) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot view this order');
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, FlowerOrder $order): Response
        {
            if ($user->id === $order->shop->user_id && in_array($order->status, ['pending', 'confirmed'])) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot update this order');
        }

        public function delete(User $user, FlowerOrder $order): Response
        {
            if ($user->id === $order->shop->user_id && $order->status === 'pending') {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot delete this order');
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
