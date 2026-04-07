<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\TeamBuilding\Services;

use App\Domains\EventPlanning\TeamBuilding\Models\TeamBuildingEvent;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class TeamBuildingService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'team:build:';
    private const RATE_LIMIT_MAX = 10;
    private const RATE_LIMIT_DECAY = 3600;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать тимбилдинг-мероприятие.
     */
    public function createEvent(
        int $facilitatorId,
        string $eventType,
        int $hoursSpent,
        int $participantsCount,
        int $priceKopecks,
        string $correlationId = '',
    ): TeamBuildingEvent {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'team_building',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($facilitatorId, $eventType, $hoursSpent, $participantsCount, $priceKopecks, $correlationId, $userId): TeamBuildingEvent {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $event = TeamBuildingEvent::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'facilitator_id' => $facilitatorId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'event_type' => $eventType,
                'hours_spent' => $hoursSpent,
                'participants_count' => $participantsCount,
                'tags' => ['team_building' => true],
            ]);

            $this->audit->log(
                action: 'team_building_created',
                subjectType: TeamBuildingEvent::class,
                subjectId: $event->id,
                old: [],
                new: $event->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Team building event created', [
                'event_id' => $event->id,
                'correlation_id' => $correlationId,
            ]);

            return $event;
        });
    }

    /**
     * Завершить мероприятие и выплатить фасилитатору.
     */
    public function completeEvent(int $eventId, string $correlationId = ''): TeamBuildingEvent
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($eventId, $correlationId): TeamBuildingEvent {
            $event = TeamBuildingEvent::findOrFail($eventId);

            if ($event->payment_status !== 'completed') {
                throw new \RuntimeException('Event payment not completed', 400);
            }

            $event->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $event->tenant_id,
                amount: $event->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['event_id' => $event->id],
            );

            $this->audit->log(
                action: 'team_building_completed',
                subjectType: TeamBuildingEvent::class,
                subjectId: $event->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $event;
        });
    }

    /**
     * Отменить мероприятие и вернуть оплату.
     */
    public function cancelEvent(int $eventId, string $correlationId = ''): TeamBuildingEvent
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($eventId, $correlationId): TeamBuildingEvent {
            $event = TeamBuildingEvent::findOrFail($eventId);

            if ($event->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed event', 400);
            }

            $previousStatus = $event->payment_status;

            $event->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousStatus === 'completed') {
                $this->wallet->credit(
                    walletId: $event->tenant_id,
                    amount: $event->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['event_id' => $event->id],
                );
            }

            $this->audit->log(
                action: 'team_building_cancelled',
                subjectType: TeamBuildingEvent::class,
                subjectId: $event->id,
                old: ['status' => $previousStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            return $event;
        });
    }

    /**
     * Получить мероприятие по идентификатору.
     */
    public function getEvent(int $eventId): TeamBuildingEvent
    {
        return TeamBuildingEvent::findOrFail($eventId);
    }

    /**
     * Получить список мероприятий клиента.
     */
    public function getUserEvents(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return TeamBuildingEvent::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
