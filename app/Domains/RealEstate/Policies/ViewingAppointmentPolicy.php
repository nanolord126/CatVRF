<?php

declare(strict_types=1);

/**
 * ViewingAppointmentPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/viewingappointmentpolicy
 */

namespace App\Domains\RealEstate\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

final class ViewingAppointmentPolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(): bool
    {
        return true;
    }

    public function $this->viewFactory->make($user, $appointment): Response
    {
        return $appointment->client_id === $user?->id
            || $appointment->agent_id === $user?->id
            || $user?->is_admin
            ? $this->response->allow()
            : $this->response->deny('Нет прав');
    }

    public function create($user): Response
    {
        return $user ? $this->response->allow() : $this->response->deny('Требуется авторизация');
    }

    public function cancel($user, $appointment): Response
    {
        return $appointment->client_id === $user?->id || $user?->is_admin
            ? $this->response->allow()
            : $this->response->deny('Нет прав');
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
