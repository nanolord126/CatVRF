<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Конструктор с зависимостями.
         */
        public function __construct(
            private WalletService $walletService,
            private string $correlationId = ''
        ) {
            // Если correlation_id не передан, генерируем новый
            $this->correlationId = $this->correlationId ?: (string) Str::uuid();
        }

        /**
         * Создать бронирование сессии с оплатой через Wallet.
         *
         * @param Coach $coach
         * @param \App\Models\User $client
         * @param \DateTimeInterface $scheduledAt
         * @param int $durationMinutes
         * @return Session
         * @throws Throwable
         */
        public function bookSession(
            Coach $coach,
            \App\Models\User $client,
            \DateTimeInterface $scheduledAt,
            int $durationMinutes = 60
        ): Session {
            // 1. Audit Log на вход
            Log::channel('audit')->info('PD Booking: Initializing session booking', [
                'coach_uuid' => $coach->uuid,
                'client_id' => $client->id,
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                'correlation_id' => $this->correlationId,
            ]);

            // 2. Fraud Control Check
            FraudControlService::check([
                'user_id' => $client->id,
                'type' => 'pd_session_booking',
                'amount' => $coach->calculateSessionPrice($durationMinutes),
                'correlation_id' => $this->correlationId,
            ]);

            // 3. Расчет стоимости
            $amount = $coach->calculateSessionPrice($durationMinutes);

            return DB::transaction(function () use ($coach, $client, $scheduledAt, $durationMinutes, $amount) {

                // 4. Оплата через Wallet (списание средств)
                $this->walletService->debit(
                    userId: $client->id,
                    amount: $amount,
                    type: 'withdrawal',
                    reason: "Оплата коуч-сессии с {$coach->name}",
                    correlationId: $this->correlationId
                );

                // 5. Создание записи сессии
                /** @var Session $session */
                $session = Session::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $coach->tenant_id,
                    'coach_id' => $coach->id,
                    'client_id' => $client->id,
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => $durationMinutes,
                    'status' => 'confirmed',
                    'amount_kopecks' => $amount,
                    'correlation_id' => $this->correlationId,
                ]);

                // 6. Генерация ссылки на видеовстречу
                $session->generateVideoLink();

                // 7. Audit Log на выход
                Log::channel('audit')->info('PD Booking: Session booked successfully', [
                    'session_uuid' => $session->uuid,
                    'amount' => $amount,
                    'correlation_id' => $this->correlationId,
                ]);

                return $session;
            });
        }

        /**
         * Отмена сессии с возвратом средств.
         */
        public function cancelSession(Session $session, string $reason): void
        {
            if ($session->status === 'cancelled') {
                throw new \Exception('Сессия уже отменена.');
            }

            DB::transaction(function () use ($session, $reason) {
                // Возврат средств в Wallet
                $this->walletService->credit(
                    userId: $session->client_id,
                    amount: $session->amount_kopecks,
                    type: 'refund',
                    reason: "Возврат за отмену сессии: {$reason}",
                    correlationId: $this->correlationId
                );

                $session->update([
                    'status' => 'cancelled',
                    'notes_after' => $reason,
                    'correlation_id' => $this->correlationId,
                ]);

                Log::channel('audit')->info('PD Booking: Session cancelled and refunded', [
                    'session_uuid' => $session->uuid,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        }
}
