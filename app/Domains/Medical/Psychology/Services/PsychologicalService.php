<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PsychologicalService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Создание/Регистрация психолога.
         */
        public function registerPsychologist(array $data, string $correlationId): Psychologist
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                // 1. Прод-контроль
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId);

                $this->logger->info('Registering new psychologist', [
                    'full_name' => $data['full_name'],
                    'correlation_id' => $correlationId,
                ]);

                $psychologist = Psychologist::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->info('Psychologist registered', [
                    'id' => $psychologist->id,
                    'uuid' => $psychologist->uuid,
                    'correlation_id' => $correlationId,
                ]);

                return $psychologist;
            });
        }

        /**
         * Создание бронирования сессии.
         */
        public function createBooking(array $data, string $correlationId): PsychologicalBooking
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId);

                $this->logger->info('Creating session booking', [
                    'client_id' => $data['client_id'],
                    'psychologist_id' => $data['psychologist_id'],
                    'correlation_id' => $correlationId,
                ]);

                // Проверка доступности специалиста (Optimistic Lock потенциально)
                $isBusy = PsychologicalBooking::where('psychologist_id', $data['psychologist_id'])
                    ->where('scheduled_at', $data['scheduled_at'])
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($isBusy) {
                    throw new \RuntimeException('Time slot is already occupied.');
                }

                $booking = PsychologicalBooking::create(array_merge($data, [
                    'uuid' => (string) Str::uuid(),
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->info('Session booking created', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);

                return $booking;
            });
        }

        /**
         * Запуск сессии (перевод из брони в активную сессию).
         */
        public function startSession(int $bookingId, string $correlationId): PsychologicalSession
        {
            return $this->db->transaction(function () use ($bookingId, $correlationId) {
                $booking = PsychologicalBooking::findOrFail($bookingId);

                if ($booking->status !== 'confirmed') {
                    throw new \RuntimeException('Only confirmed bookings can be started.');
                }

                $this->logger->info('Starting therapy session', [
                    'booking_id' => $bookingId,
                    'correlation_id' => $correlationId,
                ]);

                $session = PsychologicalSession::create([
                    'booking_id' => $bookingId,
                    'started_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $booking->update(['status' => 'completed']);

                return $session;
            });
        }

        /**
         * Завершение сессии с записью протокола (ФЗ-152).
         */
        public function finalizeSession(int $sessionId, array $notes, string $correlationId): void
        {
            $this->db->transaction(function () use ($sessionId, $notes, $correlationId) {
                $session = PsychologicalSession::findOrFail($sessionId);

                $this->logger->info('Finalizing therapy session', [
                    'session_id' => $sessionId,
                    'correlation_id' => $correlationId,
                ]);

                $session->update(array_merge($notes, [
                    'ended_at' => now(),
                ]));

                // ФЗ-152 Логирование доступа
                \App\Domains\Medical\Psychology\Models\ConfidentialityLog::create([
                    'session_id' => $sessionId,
                    'user_id' => $this->guard->id(),
                    'action' => 'edit_notes',
                    'reason' => 'Session finalization',
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
