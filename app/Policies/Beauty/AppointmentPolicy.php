<?php declare(strict_types=1);

namespace App\Policies\Beauty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        /**
         * Посмотреть запись (view).
         */
        public function view(User $user, Appointment $appointment): bool
        {
            // Только владелец записи или владелец салона/мастер может видеть
            return $user->id === $appointment->user_id
                || $user->id === $appointment->master_id
                || $user->id === $appointment->salon->owner_id;
        }

        /**
         * Создать запись (create).
         */
        public function create(User $user): bool
        {
            // Fraud check is mandatory before any creation
            return FraudControlService::check(
                userId: $user->id,
                operationType: 'beauty_appointment_create',
                amount: 0
            );
        }

        /**
         * Обновить или изменить статус (update).
         */
        public function update(User $user, Appointment $appointment): bool
        {
            // Fraud check for sensitive updates
            if (!FraudControlService::check($user->id, 'beauty_appointment_update', 0)) {
                return false;
            }

            // Обновлять может только мастер или владелец салона
            return $user->id === $appointment->master_id
                || $user->id === $appointment->salon->owner_id;
        }

        /**
         * Отменить запись (cancel).
         */
        public function cancel(User $user, Appointment $appointment): bool
        {
            // Fraud check before cancellation
            if (!FraudControlService::check($user->id, 'beauty_appointment_cancel', 0)) {
                return false;
            }

            // Отменить может клиент (владелец), мастер или салон
            return $user->id === $appointment->user_id
                || $user->id === $appointment->master_id
                || $user->id === $appointment->salon->owner_id;
        }

        /**
         * Удалить запись (delete).
         */
        public function delete(User $user, Appointment $appointment): bool
        {
            return $user->id === $appointment->salon->owner_id;
        }
}
