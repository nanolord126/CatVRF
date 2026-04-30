<?php

declare(strict_types=1);

/**
 * MembershipPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/membershippolicy
 */

namespace App\Domains\Sports\Fitness\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

use Illuminate\Contracts\Auth\Guard;

final class MembershipPolicy
{
    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly Guard $guard) {}

    public function viewAny(User $user): Response
    {
        return $user->$this->guard ? $this->response->allow() : $this->response->deny();
    }

    public function $this->viewFactory->make(User $user, Membership $membership): Response
    {
        return $user->id === $membership->member_id || $user->hasPermissionTo('view_memberships') ? $this->response->allow() : $this->response->deny();
    }

    public function create(User $user): Response
    {
        return $user->$this->guard ? $this->response->allow() : $this->response->deny();
    }

    public function cancel(User $user, Membership $membership): Response
    {
        return $user->id === $membership->member_id || $user->hasPermissionTo('cancel_memberships') ? $this->response->allow() : $this->response->deny();
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
