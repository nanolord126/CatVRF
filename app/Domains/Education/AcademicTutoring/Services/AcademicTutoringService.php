<?php declare(strict_types=1);

namespace App\Domains\Education\AcademicTutoring\Services;

use App\Domains\Education\AcademicTutoring\Models\TutorSession;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class AcademicTutoringService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'tut:sess:';
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
     * Создать сессию академического тьюторинга.
     */
    public function createSession(
        int $tutorId,
        string $subject,
        int $sessionHours,
        string $dueDate,
        int $priceKopecks,
        string $correlationId = '',
    ): TutorSession {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'tutoring',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($tutorId, $subject, $sessionHours, $dueDate, $priceKopecks, $correlationId, $userId): TutorSession {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $session = TutorSession::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'tutor_id' => $tutorId,
                'student_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'subject' => $subject,
                'session_hours' => $sessionHours,
                'due_date' => $dueDate,
                'tags' => ['tutoring' => true],
            ]);

            $this->audit->log(
                action: 'tutor_session_created',
                subjectType: TutorSession::class,
                subjectId: $session->id,
                old: [],
                new: $session->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Tutor session created', [
                'session_id' => $session->id,
                'correlation_id' => $correlationId,
            ]);

            return $session;
        });
    }

    /**
     * Завершить сессию и выплатить тьютору.
     */
    public function completeSession(int $sessionId, string $correlationId = ''): TutorSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): TutorSession {
            $session = TutorSession::findOrFail($sessionId);

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
                action: 'tutor_session_completed',
                subjectType: TutorSession::class,
                subjectId: $session->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $session;
        });
    }

    /**
     * Отменить сессию и вернуть оплату если была.
     */
    public function cancelSession(int $sessionId, string $correlationId = ''): TutorSession
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($sessionId, $correlationId): TutorSession {
            $session = TutorSession::findOrFail($sessionId);

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
                action: 'tutor_session_cancelled',
                subjectType: TutorSession::class,
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
    public function getSession(int $sessionId): TutorSession
    {
        return TutorSession::findOrFail($sessionId);
    }

    /**
     * Получить список сессий студента.
     */
    public function getUserSessions(int $studentId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return TutorSession::where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
