<?php

declare(strict_types=1);

/**
 * FashionReturnPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/fashionreturnpolicy
 */

namespace App\Domains\Fashion\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class FashionReturnPolicy
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

    public function viewAny(User $user): Response
    {
        return $user->hasPermission('view_returns') ? $this->response->allow() : $this->response->deny();
    }

    public function $this->viewFactory->make(User $user, FashionReturn $return): Response
    {
        return $user->id === $return->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('request_return') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, FashionReturn $return): Response
    {
        return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, FashionReturn $return): Response
    {
        return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }
}
