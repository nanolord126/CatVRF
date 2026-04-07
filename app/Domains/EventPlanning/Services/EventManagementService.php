<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Services;

use App\Domains\EventPlanning\Models\EventCoordination;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EventManagementService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'event_mgmt:';
    private const RATE_LIMIT_MAX = 15;
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
     * Создать координацию мероприятия.
     */
    public function createCoordination(
        int $coordinatorId,
        string $eventType,
        int $coordinationHours,
        int $priceKopecks,
        string $correlationId = '',
    ): EventCoordination {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'event_coordination',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($coordinatorId, $eventType, $coordinationHours, $priceKopecks, $correlationId, $userId): EventCoordination {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $coordination = EventCoordination::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'coordinator_id' => $coordinatorId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'event_type' => $eventType,
                'coordination_hours' => $coordinationHours,
                'tags' => ['event_management' => true],
            ]);

            $this->audit->log(
                action: 'event_coordination_created',
                subjectType: EventCoordination::class,
                subjectId: $coordination->id,
                old: [],
                new: $coordination->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Event coordination created', [
                'coordination_id' => $coordination->id,
                'correlation_id' => $correlationId,
            ]);

            return $coordination;
        });
    }

    /**
     * Завершить координацию и выплатить координатору.
     */
    public function completeCoordination(int $coordinationId, string $correlationId = ''): EventCoordination
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($coordinationId, $correlationId): EventCoordination {
            $coordination = EventCoordination::findOrFail($coordinationId);

            if ($coordination->payment_status !== 'completed') {
                throw new \RuntimeException('Coordination payment not completed', 400);
            }

            $coordination->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $coordination->tenant_id,
                amount: $coordination->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['coordination_id' => $coordination->id],
            );

            $this->audit->log(
                action: 'event_coordination_completed',
                subjectType: EventCoordination::class,
                subjectId: $coordination->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $coordination;
        });
    }

    /**
     * Отменить координацию и вернуть оплату.
     */
    public function cancelCoordination(int $coordinationId, string $correlationId = ''): EventCoordination
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($coordinationId, $correlationId): EventCoordination {
            $coordination = EventCoordination::findOrFail($coordinationId);

            if ($coordination->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed coordination', 400);
            }

            $previousStatus = $coordination->payment_status;

            $coordination->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousStatus === 'completed') {
                $this->wallet->credit(
                    walletId: $coordination->tenant_id,
                    amount: $coordination->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['coordination_id' => $coordination->id],
                );
            }

            $this->audit->log(
                action: 'event_coordination_cancelled',
                subjectType: EventCoordination::class,
                subjectId: $coordination->id,
                old: ['status' => $previousStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            return $coordination;
        });
    }

    /**
     * Получить координацию по идентификатору.
     */
    public function getCoordination(int $coordinationId): EventCoordination
    {
        return EventCoordination::findOrFail($coordinationId);
    }

    /**
     * Получить список координаций клиента.
     */
    public function getUserCoordinations(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return EventCoordination::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
