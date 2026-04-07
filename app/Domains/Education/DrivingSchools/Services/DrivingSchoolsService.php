<?php declare(strict_types=1);

namespace App\Domains\Education\DrivingSchools\Services;

use App\Domains\Education\DrivingSchools\Models\DrivingLesson;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class DrivingSchoolsService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'driving:lesson:';
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
     * Создать урок вождения.
     */
    public function createLesson(
        int $schoolId,
        string $lessonDate,
        int $durationHours,
        string $category,
        int $priceKopecks,
        string $correlationId = '',
    ): DrivingLesson {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'driving_lesson',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($schoolId, $lessonDate, $durationHours, $category, $priceKopecks, $correlationId, $userId): DrivingLesson {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $lesson = DrivingLesson::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'school_id' => $schoolId,
                'student_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'lesson_date' => $lessonDate,
                'duration_hours' => $durationHours,
                'category' => $category,
                'tags' => ['driving' => true],
            ]);

            $this->audit->log(
                action: 'driving_lesson_created',
                subjectType: DrivingLesson::class,
                subjectId: $lesson->id,
                old: [],
                new: $lesson->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Driving lesson created', [
                'lesson_id' => $lesson->id,
                'correlation_id' => $correlationId,
            ]);

            return $lesson;
        });
    }

    /**
     * Завершить урок и выплатить автошколе.
     */
    public function completeLesson(int $lessonId, string $correlationId = ''): DrivingLesson
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($lessonId, $correlationId): DrivingLesson {
            $lesson = DrivingLesson::findOrFail($lessonId);

            if ($lesson->payment_status !== 'completed') {
                throw new \RuntimeException('Lesson payment not completed', 400);
            }

            $lesson->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: (int) $lesson->tenant_id,
                amount: $lesson->payout_kopecks,
                reason: 'education_' . strtolower('PAYOUT'),
                correlationId: $correlationId,
            );

            $this->audit->log(
                action: 'driving_lesson_completed',
                subjectType: DrivingLesson::class,
                subjectId: $lesson->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $lesson;
        });
    }

    /**
     * Отменить урок и вернуть оплату.
     */
    public function cancelLesson(int $lessonId, string $correlationId = ''): DrivingLesson
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($lessonId, $correlationId): DrivingLesson {
            $lesson = DrivingLesson::findOrFail($lessonId);

            if ($lesson->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed lesson', 400);
            }

            $previousStatus = $lesson->payment_status;

            $lesson->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousStatus === 'completed') {
                $this->wallet->credit(
                walletId: (int) $lesson->tenant_id,
                amount: $lesson->total_kopecks,
                reason: 'education_' . strtolower('REFUND'),
                correlationId: $correlationId,
            );
            }

            $this->audit->log(
                action: 'driving_lesson_cancelled',
                subjectType: DrivingLesson::class,
                subjectId: $lesson->id,
                old: ['status' => $previousStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            return $lesson;
        });
    }

    /**
     * Получить урок по идентификатору.
     */
    public function getLesson(int $lessonId): DrivingLesson
    {
        return DrivingLesson::findOrFail($lessonId);
    }

    /**
     * Получить список уроков студента.
     */
    public function getUserLessons(int $studentId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return DrivingLesson::where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
