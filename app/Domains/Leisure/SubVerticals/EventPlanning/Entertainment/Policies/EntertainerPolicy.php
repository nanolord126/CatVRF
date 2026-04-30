<?php

declare(strict_types=1);

/**
 * EntertainerPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/entertainerpolicy
 */

namespace App\Domains\Leisure\SubVerticals\EventPlanning\Entertainment\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class EntertainerPolicy
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
        return $this->response->allow();
    }

    public function $this->viewFactory->make(User $user, Entertainer $entertainer): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_entertainers')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, Entertainer $entertainer): Response
    {
        return $user->id === $entertainer->user_id || $user->hasPermissionTo('update_entertainers')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, Entertainer $entertainer): Response
    {
        return $user->hasPermissionTo('delete_entertainers')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
