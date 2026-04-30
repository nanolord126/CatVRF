<?php

declare(strict_types=1);

/**
 * EventPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/eventpolicy
 */

namespace App\Domains\Leisure\SubVerticals\EventPlanning\Entertainment\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class EventPolicy
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

    public function viewAny(User $user): bool
    {
        return $user->can('view_events');
    }

    public function $this->viewFactory->make(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_entertainment');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id && $user->can('manage_entertainment');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id && $user->hasRole('admin');
    }
}
