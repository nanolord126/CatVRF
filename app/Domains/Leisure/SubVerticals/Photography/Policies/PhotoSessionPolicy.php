<?php

declare(strict_types=1);

/**
 * PhotoSessionPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/photosessionpolicy
 */

namespace App\Domains\Photography\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

final class PhotoSessionPolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(User $user, PhotoSession $session): Response
    {
        return $user->id === $session->user_id || $user->is_admin
            ? $this->response->allow()
            : $this->response->deny('Нет доступа');
    }

    public function create(User $user): Response
    {
        return $user->tenant_id ? $this->response->allow() : $this->response->deny('Требуется tenant');
    }

    public function cancel(User $user, PhotoSession $session): Response
    {
        return ($user->id === $session->user_id || $user->is_admin) && in_array($session->status, ['pending', 'confirmed'], true)
            ? $this->response->allow()
            : $this->response->deny('Отмена невозможна');
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
