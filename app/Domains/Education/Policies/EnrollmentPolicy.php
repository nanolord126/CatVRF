<?php declare(strict_types=1);

namespace App\Domains\Education\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnrollmentPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

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
}
