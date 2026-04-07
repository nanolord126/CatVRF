<?php declare(strict_types=1);

namespace App\Domains\Fashion\PersonalStyling\Services;

use App\Domains\Fashion\PersonalStyling\Models\StylingSession;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class PersonalStylingService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'styling:session:';
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
     * Создать сессию персонального стайлинга.
     */
    public function createSession(
        int $stylistId,
        string $styleType,
        int $sessionHours,
        int $priceKopecks,
        string $correlationId = '',
    ): StylingSession {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'personal_styling',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($stylistId, $styleType, $sessionHours, $priceKopecks, $correlationId, $userId): StylingSession {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $session = StylingSession::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'stylist_id' => $stylistId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'style_type' => $styleType,
                'session_hours' => $sessionHours,
                'tags' => ['personal_styling' => true],
            ]);

            $this->audit->log(
                action: 'styling_session_created',
                subjectType: StylingSession::class,
                subjectId: $session->id,
                old: [],
                new: $session->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Styling session created', [
                'session_id' => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Завершить сессию и выплатить стилисту.
     */
    public function completeSession(int $sessionId, string $correlationId = ''): StylingSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): StylingSession {
            $session = StylingSession::findOrFail($sessionId);

            if ($session->payment_status !== 'completed') {
                throw new \RuntimeException('Session payment not completed', 400);
            }

            $session->update([
                'status' => 'completed',
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
                action: 'styling_session_completed',
                subjectType: StylingSession::class,
                subjectId: $session->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $session;
        });
    }

    /**
     * Отменить сессию и вернуть оплату.
     */
    public function cancelSession(int $sessionId, string $correlationId = ''): StylingSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): StylingSession {
            $session = StylingSession::findOrFail($sessionId);

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
                action: 'styling_session_cancelled',
                subjectType: StylingSession::class,
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
    public function getSession(int $sessionId): StylingSession
    {
        return StylingSession::findOrFail($sessionId);
    }

    /**
     * Получить список сессий клиента.
     */
    public function getUserSessions(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return StylingSession::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
