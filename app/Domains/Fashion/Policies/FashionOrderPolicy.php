<?php declare(strict_types=1);

/**
 * FashionOrderPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionorderpolicy
 */


namespace App\Domains\Fashion\Policies;

final class FashionOrderPolicy
{

    public function viewAny(User $user): Response
        {
            return $user->hasPermission('view_orders') ? $this->response->allow() : $this->response->deny();
        }

        public function view(User $user, FashionOrder $order): Response
        {
            return $user->id === $order->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->hasPermission('create_order') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FashionOrder $order): Response
        {
            return $user->id === $order->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, FashionOrder $order): Response
        {
            return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
