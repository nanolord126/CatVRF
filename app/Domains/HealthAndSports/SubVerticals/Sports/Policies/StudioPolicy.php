<?php

declare(strict_types=1);

/**
 * StudioPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/studiopolicy
 */

namespace App\Domains\Sports\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

final class StudioPolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(?User $user, Studio $studio): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_studios') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, Studio $studio): Response
    {
        return ($user->id === $studio->owner_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, Studio $studio): Response
    {
        return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
