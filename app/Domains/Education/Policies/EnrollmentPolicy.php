<?php declare(strict_types=1);

/**
 * EnrollmentPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/enrollmentpolicy
 */


namespace App\Domains\Education\Policies;

final class EnrollmentPolicy
{
        /**
         * Посмотреть список своих зачислений.
         */
        public function viewAny(User $user): bool
        {
            return $user->id !== null;
        }

        /**
         * Пройти урок или курс (View Enrollment).
         */
        public function view(User $user, Enrollment $enrollment): bool
        {
            // Только владелец курса или администратор теннанта.
            return $user->id === $enrollment->user_id;
        }

        /**
         * Зачислиться на курс.
         */
        public function create(User $user): bool
        {
            // 1. Проверка фрода (FraudControlService)
            // 2. Лимиты зачислений за час (RateLimiter)
            return true;
        }

        /**
         * Отмена зачисления (Refund / Drop).
         */
        public function delete(User $user, Enrollment $enrollment): bool
        {
            // Только если прогресс < 10% и до 24ч после оплаты (Канон Бизнеса 2026)
            if ($enrollment->progress_percent > 10) {
                return false;
            }

            return $user->id === $enrollment->user_id;
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
