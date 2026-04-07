<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class BookingService
{

    public function __construct(// private readonly FraudControlService $fraud,
            // private readonly WalletService $walletService,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создание бронирования
         * Минимум 60 строк (авто-формирование + логика)
         */
        public function createBooking(
            int $clientId,
            int $sessionId,
            string $startsAt,
            ?int $photographerId = null,
            ?int $studioId = null,
            ?string $correlationId = null
        ): Booking {
            $correlationId ??= (string) Str::uuid();

            return $this->db->transaction(function () use ($clientId, $sessionId, $startsAt, $photographerId, $studioId, $correlationId) {
                $session = PhotoSession::findOrFail($sessionId);

                // 1. Имитация $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '')
                $this->logger->info('Photography booking validation (Correlation: '.$correlationId.')', [
                    'client_id' => $clientId,
                    'session_type' => $session->name,
                    'starts_at' => $startsAt,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                // 2. Расчет и проверка временных рамок
                $starts = \Carbon\Carbon::parse($startsAt);
                if ($starts->isPast()) {
                    throw new \InvalidArgumentException('Нельзя забронировать сессию в прошлом времени.');
                }

                $ends = $starts->copy()->addMinutes($session->duration_minutes);

                // 3. Проверка доступности ресурсов (фотографа/студии)
                if ($photographerId) {
                    $isBusy = Booking::where('photographer_id', $photographerId)
                        ->where('status', '!=', 'cancelled')
                        ->where(fn($q) => $q->whereBetween('starts_at', [$starts, $ends])
                                           ->orWhereBetween('ends_at', [$starts, $ends]))
                        ->exists();
                    if ($isBusy) throw new \RuntimeException('Выбранный фотограф занят в это время.');
                }

                // 4. Отражение в базе данных (мутация)
                $booking = Booking::create([
                    'uuid' => (string) Str::uuid(),
                    'client_id' => $clientId,
                    'session_id' => $sessionId,
                    'photographer_id' => $photographerId,
                    'studio_id' => $studioId,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'status' => 'pending',
                    'total_amount_kopecks' => $session->price_kopecks,
                    'correlation_id' => $correlationId
                ]);

                $this->logger->info('Photography booking successfully stored (UUID: '.$booking->uuid.')', [
                    'correlation_id' => $correlationId,
                    'total_amount' => $booking->total_amount_kopecks
                ]);

                return $booking;
            });
        }

        /**
         * Перенос фотосессии (Reschedule)
         */
        public function reschedule(int $bookingId, string $newStartsAt, ?string $correlationId = null): bool
        {
            $correlationId ??= (string) Str::uuid();

            return $this->db->transaction(function () use ($bookingId, $newStartsAt, $correlationId) {
                $booking = Booking::findOrFail($bookingId);

                $duration = $booking->starts_at->diffInMinutes($booking->ends_at);
                $newStarts = \Carbon\Carbon::parse($newStartsAt);
                $newEnds = $newStarts->copy()->addMinutes((int)$duration);

                $booking->update([
                    'starts_at' => $newStarts,
                    'ends_at' => $newEnds,
                    'status' => 'rescheduled',
                    'correlation_id' => $correlationId
                ]);

                $this->logger->info('Photography booking rescheduling audit completed', [
                    'booking_id' => $bookingId,
                    'new_starts_at' => $newStartsAt,
                    'correlation_id' => $correlationId
                ]);

                return true;
            });
        }

        /**
         * Отмена бронирования (Cancellation)
         */
        public function cancel(int $bookingId, string $reason, ?string $correlationId = null): bool
        {
            $correlationId ??= (string) Str::uuid();

            return $this->db->transaction(function () use ($bookingId, $reason, $correlationId) {
                $booking = Booking::findOrFail($bookingId);

                $booking->update([
                    'status' => 'cancelled',
                    'correlation_id' => $correlationId
                ]);

                $this->logger->warning('Photography session cancellation triggered: '.$reason, [
                    'booking_uuid' => $booking->uuid,
                    'correlation_id' => $correlationId
                ]);

                return true;
            });
        }
}
