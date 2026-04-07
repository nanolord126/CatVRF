<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageTutoring\Services;

use App\Domains\Education\LanguageTutoring\Models\TutoringSession;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class LanguageTutoringService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'tutor:session:';
    private const RATE_LIMIT_MAX = 28;
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
     * Создать сессию языкового тьюторинга.
     */
    public function createSession(
        int $tutorId,
        string $sessionDate,
        int $durationHours,
        string $language,
        int $priceKopecks,
        string $correlationId = '',
    ): TutoringSession {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'tutoring',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($tutorId, $sessionDate, $durationHours, $language, $priceKopecks, $correlationId, $userId): TutoringSession {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $session = TutoringSession::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'tutor_id' => $tutorId,
                'student_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'session_date' => $sessionDate,
                'duration_hours' => $durationHours,
                'language' => $language,
                'tags' => ['tutoring' => true],
            ]);

            $this->audit->log(
                action: 'language_session_created',
                subjectType: TutoringSession::class,
                subjectId: $session->id,
                old: [],
                new: $session->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Language tutoring session created', [
                'session_id' => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Завершить сессию и выплатить тьютору.
     */
    public function completeSession(int $sessionId, string $correlationId = ''): TutoringSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): TutoringSession {
            $session = TutoringSession::findOrFail($sessionId);

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
                action: 'language_session_completed',
                subjectType: TutoringSession::class,
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
    public function cancelSession(int $sessionId, string $correlationId = ''): TutoringSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): TutoringSession {
            $session = TutoringSession::findOrFail($sessionId);

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
                action: 'language_session_cancelled',
                subjectType: TutoringSession::class,
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
    public function getSession(int $sessionId): TutoringSession
    {
        return TutoringSession::findOrFail($sessionId);
    }

    /**
     * Получить список сессий студента.
     */
    public function getUserSessions(int $studentId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return TutoringSession::where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
