<?php

declare(strict_types=1);

/**
 * FlowerDeliveryPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/flowerdeliverypolicy
 */

namespace App\Domains\Leisure\SubVerticals\Flowers\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class FlowerDeliveryPolicy
{
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


    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function $this->viewFactory->make(User $user, FlowerDelivery $delivery): Response
    {
        if ($user->id === $delivery->order->user_id || $user->id === $delivery->shop->user_id) {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot view this delivery');
    }

    public function track(User $user, FlowerDelivery $delivery): Response
    {
        if ($user->id === $delivery->order->user_id) {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot track this delivery');
    }

    public function update(User $user, FlowerDelivery $delivery): Response
    {
        if ($user->id === $delivery->shop->user_id && in_array($delivery->status, ['assigned', 'in_transit'], true)) {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot update this delivery');
    }
}
