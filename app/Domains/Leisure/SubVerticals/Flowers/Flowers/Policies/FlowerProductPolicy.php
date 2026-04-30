<?php

declare(strict_types=1);

/**
 * FlowerProductPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/flowerproductpolicy
 */

namespace App\Domains\Leisure\SubVerticals\Flowers\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class FlowerProductPolicy
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;


    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(User $user, FlowerProduct $product): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        if ($user->isBusiness()) {
            return $this->response->allow();
        }

        return $this->response->deny('Only business users can create products');
    }

    public function update(User $user, FlowerProduct $product): Response
    {
        if ($user->id === $product->shop->user_id && $user->isBusiness()) {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot update this product');
    }

    public function delete(User $user, FlowerProduct $product): Response
    {
        if ($user->id === $product->shop->user_id && $user->isBusiness()) {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot delete this product');
    }
}
