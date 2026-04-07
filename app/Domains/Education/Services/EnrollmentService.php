<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Domains\Education\Events\StudentEnrolled;
use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\Enrollment;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EnrollmentService
{
    private const COMMISSION_RATE = 0.14;

    public function __construct(
        private WalletService $walletService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Зачисление студента на курс (B2C: оплата модулем или полная сумма).
     */
    public function enrollStudent(int $userId, int $courseId, string $type = 'full'): Enrollment
    {
        $correlationId = (string) Str::uuid();

        return $this->db->transaction(function () use ($userId, $courseId, $type, $correlationId): Enrollment {
            $course = Course::findOrFail($courseId);

            $this->fraud->check(
                userId: $userId,
                operationType: 'enroll_student',
                amount: $course->price ?? 0,
                correlationId: $correlationId,
            );

            $existing = Enrollment::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->first();

            if ($existing && $existing->isActive()) {
                throw new \RuntimeException('Вы уже зачислены на данный курс и имеете активный доступ.');
            }

            $price = ($type === 'full') ? $course->price : ($course->subscription_price ?? $course->price);

            if ($price > 0) {
                $this->walletService->debit(
                    userId: $userId,
                    amount: $price,
                    reason: "Оплата курса: {$course->title} ({$type})",
                    correlationId: $correlationId,
                );
            }

            $enrollment = Enrollment::updateOrCreate(
                ['user_id' => $userId, 'course_id' => $courseId],
                [
                    'status' => 'active',
                    'type' => $type,
                    'correlation_id' => $correlationId,
                    'price_paid' => $price,
                    'tags' => ['enrollment_type' => $type],
                ],
            );

            $this->audit->log(
                action: 'student_enrolled',
                subjectType: Enrollment::class,
                subjectId: $enrollment->id,
                old: [],
                new: ['user_id' => $userId, 'course_id' => $courseId, 'type' => $type],
                correlationId: $correlationId,
            );

            $this->logger->info('Student enrolled in course', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'enrollment_id' => $enrollment->id,
                'correlation_id' => $correlationId,
            ]);

            event(new StudentEnrolled($enrollment, $correlationId));

            return $enrollment;
        });
    }

    /**
     * Массовое зачисление сотрудников (B2B).
     */
    public function enrollCorporateGroup(int $businessGroupId, int $courseId, array $userIds): void
    {
        $correlationId = (string) Str::uuid();

        $this->db->transaction(function () use ($businessGroupId, $courseId, $userIds, $correlationId): void {
            $course = Course::findOrFail($courseId);

            foreach ($userIds as $userId) {
                $this->enrollStudent($userId, $courseId, 'b2b');
            }

            $this->logger->info('Corporate group enrolled in course', [
                'business_group_id' => $businessGroupId,
                'course_id' => $courseId,
                'students_count' => count($userIds),
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Приостановление доступа студента.
     */
    public function suspendAccess(int $enrollmentId, string $reason): void
    {
        $correlationId = (string) Str::uuid();
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $previousStatus = $enrollment->status;

        $enrollment->update([
            'status' => 'suspended',
            'correlation_id' => $correlationId,
        ]);

        $this->audit->log(
            action: 'enrollment_suspended',
            subjectType: Enrollment::class,
            subjectId: $enrollmentId,
            old: ['status' => $previousStatus],
            new: ['status' => 'suspended', 'reason' => $reason],
            correlationId: $correlationId,
        );

        $this->logger->warning('Student enrollment suspended', [
            'enrollment_id' => $enrollmentId,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);
    }
}
