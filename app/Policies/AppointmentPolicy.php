<?php declare(strict_types=1);

namespace App\Policies;


use Psr\Log\LoggerInterface;
final class AppointmentPolicy extends Model
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    use HandlesAuthorization;

        /**
         * Может ли пользователь видеть запись?
         * Клиент видит свою запись, мастер видит свою запись, бизнес видит все.
         */
        public function view(User $user, Appointment $appointment): bool
        {
            // CANON 2026: Strict tenant scoping check
            if (isset($appointment->tenant_id) && $user->tenant_id !== $appointment->tenant_id && !$user->hasRole('admin')) {
                $this->logger->warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'user_tenant_id' => $user->tenant_id,
                    'model_tenant_id' => $appointment->tenant_id,
                ]);
                return false;
            }

            $allowed = (
                $user->id === $appointment->client_id || // клиент
                $user->id === $appointment->master_id || // мастер
                $user->tenant_id === $appointment->salon->tenant_id || // бизнес
                $user->hasRole('admin')
            );

            if (!$allowed) {
                $this->logger->warning('Unauthorized appointment view attempt', [
                    'user_id' => $user->id,
                    'appointment_id' => $appointment->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь видеть все записи?
         * Только администратор или менеджер салона.
         */
        public function viewAny(User $user): bool
        {
            return $user->hasAnyRole(['admin', 'finance_manager']);
        }

        /**
         * Может ли пользователь создать запись?
         * Клиент может забронировать, бизнес может создать для других.
         */
        public function create(User $user): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            $allowed = $user->email_verified_at !== null;

            if (!$allowed) {
                $this->logger->info('Unverified user appointment creation attempt', [
                    'user_id' => $user->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь обновить запись?
         * Только статус "pending" (до подтверждения).
         * После "confirmed" - только бизнес может перенести/отменить.
         */
        public function update(User $user, Appointment $appointment): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            $allowed = false;

            // Клиент может менять только pending запись
            if ($user->id === $appointment->client_id && $appointment->status === 'pending' && !$appointment->confirmed_at) {
                $allowed = true;
            }

            // Бизнес может менять любую запись кроме completed/cancelled
            if ($user->tenant_id === $appointment->salon->tenant_id && !in_array($appointment->status, ['completed', 'cancelled'])) {
                $allowed = true;
            }

            if (!$allowed) {
                $this->logger->warning('Unauthorized appointment update attempt', [
                    'user_id' => $user->id,
                    'appointment_id' => $appointment->id,
                    'appointment_status' => $appointment->status,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли пользователь отменить запись?
         * Клиент может отменить только pending запись.
         * Бизнес может отменить в любой момент (но до завершения).
         */
        public function cancel(User $user, Appointment $appointment): bool
        {
            $allowed = false;

            // Клиент может отменить pending запись
            if ($user->id === $appointment->client_id && $appointment->status === 'pending') {
                $allowed = true;
            }

            // Клиент может отменить confirmed в течение 24 часов
            if (
                $user->id === $appointment->client_id &&
                $appointment->status === 'confirmed' &&
                $appointment->confirmed_at &&
                now()->diffInHours($appointment->confirmed_at) < 24
            ) {
                $allowed = true;
            }

            // Бизнес может отменить любую запись (кроме completed)
            if (
                $user->tenant_id === $appointment->salon->tenant_id &&
                $appointment->status !== 'completed'
            ) {
                $allowed = true;
            }

            if (!$allowed) {
                $this->logger->warning('Unauthorized appointment cancellation attempt', [
                    'user_id' => $user->id,
                    'appointment_id' => $appointment->id,
                ]);
            }

            return $allowed;
        }

        /**
         * Может ли мастер подтвердить запись?
         */
        public function confirm(User $user, Appointment $appointment): bool
        {
            $allowed = (
                $user->id === $appointment->master_id &&
                $appointment->status === 'pending' &&
                !$appointment->confirmed_at
            );

            return $allowed;
        }

        /**
         * Может ли мастер отметить как "in progress"?
         */
        public function startService(User $user, Appointment $appointment): bool
        {
            $allowed = (
                $user->id === $appointment->master_id &&
                $appointment->status === 'confirmed' &&
                $appointment->confirmed_at !== null &&
                !$appointment->started_at
            );

            return $allowed;
        }

        /**
         * Может ли мастер завершить услугу?
         * После завершения автоматически списываются расходники.
         */
        public function completeService(User $user, Appointment $appointment): bool
        {
            $allowed = (
                $user->id === $appointment->master_id &&
                $appointment->status === 'in_progress' &&
                $appointment->started_at !== null &&
                !$appointment->completed_at
            );

            return $allowed;
        }

        /**
         * Может ли клиент оставить отзыв?
         * Только после завершения услуги.
         */
        public function reviewAppointment(User $user, Appointment $appointment): bool
        {
            return (
                $user->id === $appointment->client_id &&
                $appointment->status === 'completed' &&
                !$appointment->reviewed_at
            );
        }

        /**
         * Может ли пользователь просмотреть портфолио мастера?
         */
        public function viewPortfolio(User $user, Appointment $appointment): bool
        {
            return true; // портфолио публичное
        }

        /**
         * Может ли пользователь просмотреть расчётные документы?
         */
        public function viewInvoice(User $user, Appointment $appointment): bool
        {
            return (
                $user->id === $appointment->client_id ||
                $user->id === $appointment->master_id ||
                $user->tenant_id === $appointment->salon->tenant_id ||
                $user->hasRole('admin')
            );
        }

        /**
         * Может ли пользователь скачать квитанцию?
         * Только после оплаты.
         */
        public function downloadReceipt(User $user, Appointment $appointment): bool
        {
            if ($appointment->paid_at === null) {
                return false;
            }

            return (
                $user->id === $appointment->client_id ||
                $user->tenant_id === $appointment->salon->tenant_id ||
                $user->hasRole('admin')
            );
        }

        /**
         * Может ли администратор обновить запись?
         */
        public function forceUpdate(User $user, Appointment $appointment): bool
        {
            return $user->hasRole('admin');
        }

        /**
         * Может ли администратор удалить запись?
         * Soft delete для аудита.
         */
        public function delete(User $user, Appointment $appointment): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->hasRole('admin');
        }

        /**
         * Может ли администратор восстановить запись?
         */
        public function restore(User $user, Appointment $appointment): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return $user->hasRole('admin');
        }

        /**
         * Может ли администратор hard-удалить запись?
         * ЗАПРЕЩЕНО.
         */
        public function forceDelete(User $user, Appointment $appointment): bool
        {
            // CANON 2026 FRAUD: Predict/check operation before mutating
            $fraudScore = 0; // fraud check at service layer
            if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
                $this->logger->warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                    'user_id' => $user->id,
                    'score' => $fraudScore
                ]);
                return false;
            }

            return false;
        }
}
