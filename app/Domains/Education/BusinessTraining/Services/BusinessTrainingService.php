<?php declare(strict_types=1);

namespace App\Domains\Education\BusinessTraining\Services;

use App\Domains\Education\BusinessTraining\Models\TrainingSession;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class BusinessTrainingService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'train:sess:';
    private const RATE_LIMIT_MAX = 14;
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
     * Создать сессию бизнес-тренинга.
     */
    public function createSession(
        int $providerId,
        string $trainingType,
        int $trainingHours,
        string $dueDate,
        int $priceKopecks,
        string $correlationId = '',
    ): TrainingSession {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'training',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($providerId, $trainingType, $trainingHours, $dueDate, $priceKopecks, $correlationId, $userId): TrainingSession {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $session = TrainingSession::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'provider_id' => $providerId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'training_type' => $trainingType,
                'training_hours' => $trainingHours,
                'due_date' => $dueDate,
                'tags' => ['training' => true],
            ]);

            $this->audit->log(
                action: 'training_session_created',
                subjectType: TrainingSession::class,
                subjectId: $session->id,
                old: [],
                new: $session->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Training session created', [
                'session_id' => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Завершить тренинг и выплатить провайдеру.
     */
    public function completeSession(int $sessionId, string $correlationId = ''): TrainingSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): TrainingSession {
            $session = TrainingSession::findOrFail($sessionId);

            if ($session->payment_status !== 'completed') {
                throw new \RuntimeException('Session payment not completed', 400);
            }

            $session->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: (int) $session->tenant_id,
                amount: $session->payout_kopecks,
                reason: 'education_' . strtolower('PAYOUT'),
                correlationId: $correlationId,
            );

            $this->audit->log(
                action: 'training_session_completed',
                subjectType: TrainingSession::class,
                subjectId: $session->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $session;
        });
    }

    /**
     * Отменить тренинг и вернуть оплату.
     */
    public function cancelSession(int $sessionId, string $correlationId = ''): TrainingSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): TrainingSession {
            $session = TrainingSession::findOrFail($sessionId);

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
                walletId: (int) $session->tenant_id,
                amount: $session->total_kopecks,
                reason: 'education_' . strtolower('REFUND'),
                correlationId: $correlationId,
            );
            }

            $this->audit->log(
                action: 'training_session_cancelled',
                subjectType: TrainingSession::class,
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
    public function getSession(int $sessionId): TrainingSession
    {
        return TrainingSession::findOrFail($sessionId);
    }

    /**
     * Получить список сессий клиента.
     */
    public function getUserSessions(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return TrainingSession::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
