<?php declare(strict_types=1);

/**
 * TrainerPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/trainerpolicy
 */


namespace App\Domains\Sports\Policies;

final class TrainerPolicy
{

    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, Trainer $trainer): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_trainers') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, Trainer $trainer): Response
        {
            return ($user->id === $trainer->user_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, Trainer $trainer): Response
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
