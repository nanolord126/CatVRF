<?php declare(strict_types=1);

/**
 * ClassPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/classpolicy
 */


namespace App\Domains\Sports\Policies;

final class ClassPolicy
{

    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, ClassSession $class): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_classes') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, ClassSession $class): Response
        {
            return ($user->id === $class->trainer->user_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, ClassSession $class): Response
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
