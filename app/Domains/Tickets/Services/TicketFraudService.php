<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;




use App\Services\Fraud\FraudMLService;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class TicketFraudService
{
    public function __construct(
        private readonly FraudMLService $fraudML,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}



    /**
         * Проверка операции покупки билетов на фрод.
         * Защита от перекупов и массовых списаний.
         */
        public function check(int $userId, int $eventId, int $typeId, int $quantity, string $correlationId): bool
        {
            // 1. Прокси на основной сервис
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ticket_purchase', amount: 0, correlationId: $correlationId ?? '');

            // 2. Специфичное правило: Лимит на мероприятие
            $eventMax = \App\Domains\Tickets\Models\Event::findOrFail($eventId)->max_tickets_per_user;
            $userTotal = Ticket::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->where('status', '!=', 'cancelled')
                ->sum('quantity');

            if (($userTotal + $quantity) > $eventMax) {
                $this->logger->warning('Exceeded max tickets per event', [
                    'user_id' => $userId,
                    'event_id' => $eventId,
                    'current' => $userTotal,
                    'attempted' => $quantity,
                    'correlation_id' => $correlationId
                ]);
                throw new \DomainException("Превышено максимальное количество билетов на одного пользователя ({$eventMax})");
            }

            // 3. Специфичное правило: Скорость покупки (защита от ботов)
            $cacheKey = "fraud_tickets:{$userId}:{$eventId}";
            $attempts = (int) $this->cache->get($cacheKey, 0);

            if ($attempts > 3) {
                $this->logger->error('Bot suspected: too many purchase attempts', [
                    'user_id' => $userId,
                    'event_id' => $eventId,
                    'correlation_id' => $correlationId
                ]);
                throw new \DomainException('Слишком много попыток покупки. Попробуйте через 5 минут.');
            }

            $this->cache->put($cacheKey, $attempts + 1, now()->addMinutes(5));

            // 4. ML Score
            $mlDecision = $this->fraudML->scoreOperation(
                userId: $userId,
                operationType: 'ticket_purchase',
                amount: $quantity,
                ipAddress: $this->request->ip() ?? '',
                deviceFingerprint: null,
                context: ['event_id' => $eventId, 'type_id' => $typeId],
                correlationId: $correlationId,
            );
            if (($mlDecision['score'] ?? 0.0) > 0.8) {
                $this->logger->error('FraudML blocked ticket purchase', [
                    'user_id' => $userId,
                    'event_id' => $eventId,
                    'score' => $mlDecision['score'] ?? null,
                    'correlation_id' => $correlationId,
                ]);
                throw new \DomainException('Операция заблокирована системой безопасности');
            }

            return true;
        }

        /**
         * Проверка при чекине (валидация QR).
         */
        public function validateCheckIn(string $qrCode, int $checkerId): void
        {
            $lockKey = "checkin_lock:" . md5($qrCode);

            if (!$this->cache->add($lockKey, 1, now()->addSeconds(30))) {
                $this->logger->warning('Double check-in attempt detected', [
                    'qr_code' => $qrCode,
                    'checker_id' => $checkerId,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                throw new \RuntimeException('Этот QR-код уже сканируется в данный момент');
            }
        }
}
