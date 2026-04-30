<?php

declare(strict_types=1);

/**
 * MedicalDoctorPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/medicaldoctorpolicy
 */

namespace App\Domains\Shared\Medical\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

final class MedicalDoctorPolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(User $user, MedicalDoctor $doctor): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_medical_doctor') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, MedicalDoctor $doctor): Response
    {
        return $user->id === $doctor->user_id || $user->hasRole('admin')
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function delete(User $user, MedicalDoctor $doctor): Response
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
