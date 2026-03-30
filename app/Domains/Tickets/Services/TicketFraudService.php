<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketFraudService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Проверка операции покупки билетов на фрод.
         * Защита от перекупов и массовых списаний.
         */
        public function check(int $userId, int $eventId, int $typeId, int $quantity, string $correlationId): bool
        {
            // 1. Прокси на основной сервис
            FraudControlService::check($userId, 'ticket_purchase', [
                'event_id' => $eventId,
                'ticket_type_id' => $typeId,
                'quantity' => $quantity,
                'correlation_id' => $correlationId
            ]);

            // 2. Специфичное правило: Лимит на мероприятие
            $eventMax = \App\Domains\Tickets\Models\Event::findOrFail($eventId)->max_tickets_per_user;
            $userTotal = Ticket::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->where('status', '!=', 'cancelled')
                ->sum('quantity');

            if (($userTotal + $quantity) > $eventMax) {
                Log::channel('fraud_alert')->warning('Exceeded max tickets per event', [
                    'user_id' => $userId,
                    'event_id' => $eventId,
                    'current' => $userTotal,
                    'attempted' => $quantity,
                    'correlation_id' => $correlationId
                ]);
                throw new \Exception("Превышено максимальное количество билетов на одного пользователя ({$eventMax})");
            }

            // 3. Специфичное правило: Скорость покупки (защита от ботов)
            $cacheKey = "fraud_tickets:{$userId}:{$eventId}";
            $attempts = (int) Cache::get($cacheKey, 0);

            if ($attempts > 3) {
                Log::channel('fraud_alert')->error('Bot suspected: too many purchase attempts', [
                    'user_id' => $userId,
                    'event_id' => $eventId,
                    'correlation_id' => $correlationId
                ]);
                throw new \Exception('Слишком много попыток покупки. Попробуйте через 5 минут.');
            }

            Cache::put($cacheKey, $attempts + 1, now()->addMinutes(5));

            // 4. ML Score (если доступно)
            // В 2026 тут вызов FraudMLService::scoreOperation()
            // if (app(FraudMLService::class)->score() > 0.8) throw Error...

            return true;
        }

        /**
         * Проверка при чекине (валидация QR).
         */
        public function validateCheckIn(string $qrCode, int $checkerId): void
        {
            $lockKey = "checkin_lock:" . md5($qrCode);

            if (!Cache::add($lockKey, 1, now()->addSeconds(30))) {
                Log::channel('audit')->warning('Double check-in attempt detected', [
                    'qr_code' => $qrCode,
                    'checker_id' => $checkerId
                ]);
                throw new \Exception('Этот QR-код уже сканируется в данный момент');
            }
        }
}
