<?php declare(strict_types=1);

namespace App\Domains\Fashion\PersonalShopping\Services;

use App\Domains\Fashion\PersonalShopping\Models\ShoppingSession;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class PersonalShoppingService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'shopping:session:';
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
     * Создать сессию персонального шопинга.
     */
    public function createSession(
        int $shopperId,
        int $durationHours,
        int $priceKopecks,
        string $correlationId = '',
    ): ShoppingSession {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'personal_shopping',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($shopperId, $durationHours, $priceKopecks, $correlationId, $userId): ShoppingSession {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $session = ShoppingSession::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'shopper_id' => $shopperId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'duration_hours' => $durationHours,
                'items_purchased' => [],
                'tags' => ['personal_shopping' => true],
            ]);

            $this->audit->log(
                action: 'shopping_session_created',
                subjectType: ShoppingSession::class,
                subjectId: $session->id,
                old: [],
                new: $session->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Personal shopping session created', [
                'session_id' => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Завершить сессию и выплатить шопперу.
     */
    public function completeSession(int $sessionId, array $itemsPurchased = [], string $correlationId = ''): ShoppingSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $itemsPurchased, $correlationId): ShoppingSession {
            $session = ShoppingSession::findOrFail($sessionId);

            if ($session->payment_status !== 'completed') {
                throw new \RuntimeException('Session payment not completed', 400);
            }

            $session->update([
                'status' => 'completed',
                'items_purchased' => $itemsPurchased,
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $session->tenant_id,
                amount: $session->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['session_id' => $session->id],
            );

            $this->audit->log(
                action: 'shopping_session_completed',
                subjectType: ShoppingSession::class,
                subjectId: $session->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed', 'items_count' => count($itemsPurchased)],
                correlationId: $correlationId,
            );

            return $session;
        });
    }

    /**
     * Отменить сессию и вернуть оплату.
     */
    public function cancelSession(int $sessionId, string $correlationId = ''): ShoppingSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): ShoppingSession {
            $session = ShoppingSession::findOrFail($sessionId);

            if ($session->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed session', 400);
            }

            $previousStatus = $session->payment_status;

            $session->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousStatus === 'completed') {
                $this->wallet->credit(
                    walletId: $session->tenant_id,
                    amount: $session->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['session_id' => $session->id],
                );
            }

            $this->audit->log(
                action: 'shopping_session_cancelled',
                subjectType: ShoppingSession::class,
                subjectId: $session->id,
                old: ['status' => $previousStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            return $session;
        });
    }

    /**
     * Получить сессию по идентификатору.
     */
    public function getSession(int $sessionId): ShoppingSession
    {
        return ShoppingSession::findOrFail($sessionId);
    }

    /**
     * Получить список сессий клиента.
     */
    public function getUserSessions(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ShoppingSession::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
