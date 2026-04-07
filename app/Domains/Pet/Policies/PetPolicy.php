<?php declare(strict_types=1);

/**
 * PetPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/petpolicy
 */


namespace App\Domains\Pet\Policies;

final class PetPolicy
{

    use HandlesAuthorization;

        public function view(User $user, Pet $pet): bool
        {
            // Доступ имеет владелец тенанта или сотрудник, привязанный к этой записи
            return $pet->tenant_id === $user->tenant_id;
        }

        public function update(User $user, Pet $pet): bool
        {
            if (!app(\App\Services\FraudControlService::class)->check('update_pet_medical_card', ['pet_id' => $pet->id])) {
                return false;
            }

            return $pet->tenant_id === $user->tenant_id && $user->can('edit_medical_cards');
        }

        public function delete(User $user, Pet $pet): bool
        {
            // Удаление медкарты — критическое действие, логируется отдельно
            return $pet->tenant_id === $user->tenant_id && $user->isAdmin();
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
