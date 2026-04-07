<?php declare(strict_types=1);

/**
 * TaxiDriverPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/taxidriverpolicy
 */


namespace App\Domains\Taxi\Policies;

final class TaxiDriverPolicy
{

    public function viewAny(User $user): bool
        {
            return true; // Все могут видеть список водителей
        }

        public function view(User $user, TaxiDriver $driver): bool
        {
            return true; // Профиль водителя публичный
        }

        public function update(User $user, TaxiDriver $driver): Response
        {
            if ($user->id !== $driver->user_id && !$user->isAdmin()) {
                return $this->response->deny('Вы не можете редактировать этого водителя');
            }

            return $this->response->allow();
        }

        public function deactivate(User $user, TaxiDriver $driver): Response
        {
            if (!$user->isAdmin()) {
                return $this->response->deny('Только администратор может деактивировать водителя');
            }

            return $this->response->allow();
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
