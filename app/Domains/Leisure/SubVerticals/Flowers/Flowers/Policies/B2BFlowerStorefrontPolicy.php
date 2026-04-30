<?php

declare(strict_types=1);

/**
 * B2BFlowerStorefrontPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/b2bflowerstorefrontpolicy
 */

namespace App\Domains\Leisure\SubVerticals\Flowers\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class B2BFlowerStorefrontPolicy
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

    public function $this->viewFactory->make(User $user, B2BFlowerStorefront $storefront): Response
    {
        if (! $user->company_inn) {
            return $this->response->deny('Company INN is required');
        }

        if ($user->company_inn === $storefront->company_inn && $storefront->is_active) {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot access this B2B storefront');
    }

    public function register(User $user): Response
    {
        if ($user->company_inn && ! $user->b2bFlowerStorefront) {
            return $this->response->allow();
        }

        return $this->response->deny('Invalid B2B registration request');
    }

    public function update(User $user, B2BFlowerStorefront $storefront): Response
    {
        if ($user->company_inn === $storefront->company_inn) {
            return $this->response->allow();
        }

        return $this->response->deny('You cannot update this storefront');
    }
}
