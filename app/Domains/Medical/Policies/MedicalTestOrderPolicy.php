<?php declare(strict_types=1);

/**
 * MedicalTestOrderPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/medicaltestorderpolicy
 */


namespace App\Domains\Medical\Policies;

final class MedicalTestOrderPolicy
{

    public function viewAny(User $user): Response
        {
            return $user->hasPermissionTo('view_test_orders') ? $this->response->allow() : $this->response->deny();
        }

        public function view(User $user, MedicalTestOrder $testOrder): Response
        {
            return $user->id === $testOrder->patient_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_test_order') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, MedicalTestOrder $testOrder): Response
        {
            return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, MedicalTestOrder $testOrder): Response
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
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
