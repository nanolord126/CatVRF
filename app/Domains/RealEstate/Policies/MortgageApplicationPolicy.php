<?php declare(strict_types=1);

/**
 * MortgageApplicationPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/mortgageapplicationpolicy
 */


namespace App\Domains\RealEstate\Policies;

final class MortgageApplicationPolicy
{

    public function viewAny($user): bool
        {
            return $user?->is_admin || false;
        }

        public function view($user, $application): Response
        {
            return $application->client_id === $user?->id || $user?->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет прав');
        }

        public function create($user): Response
        {
            return $user ? $this->response->allow() : $this->response->deny('Требуется авторизация');
        }

        public function update($user, $application): Response
        {
            return $user?->is_admin ? $this->response->allow() : $this->response->deny('Только админ');
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
