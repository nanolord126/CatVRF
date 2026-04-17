<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Domains\Education\Models\Enrollment;
use App\Domains\Education\Models\Course;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\Security\IdempotencyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class CourseEnrollmentService
{
    private const MARKETPLACE_COMMISSION_PERCENT = 15;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private IdempotencyService $idempotency,
        private EducationMilestonePaymentService $milestonePayment,
    ) {}

    public function enrollWithSplitPayment(
        int $userId,
        int $courseId,
        ?int $corporateContractId,
        string $correlationId,
        ?string $idempotencyKey = null,
    ): array {
        if ($idempotencyKey !== null) {
            $cached = $this->idempotency->check('education_enrollment', $idempotencyKey, [
                'user_id' => $userId,
                'course_id' => $courseId,
            ], tenant()->id);
            if (!empty($cached)) {
                return $cached;
            }
        }

        $this->fraud->check([
            'operation_type' => 'course_enrollment',
            'user_id' => $userId,
            'course_id' => $courseId,
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($userId, $courseId, $corporateContractId, $correlationId, $idempotencyKey) {
            $course = Course::findOrFail($courseId);

            $existingEnrollment = Enrollment::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->first();

            if ($existingEnrollment !== null) {
                throw new \DomainException('User already enrolled in this course');
            }

            $isB2B = $corporateContractId !== null;
            $mode = $isB2B ? 'b2b' : 'b2c';
            $totalPrice = $isB2B 
                ? ($course->corporate_price_kopecks ?? $course->price_kopecks)
                : $course->price_kopecks;

            $marketplaceShare = (int) ($totalPrice * self::MARKETPLACE_COMMISSION_PERCENT / 100);
            $teacherShare = $totalPrice - $marketplaceShare;

            $enrollment = Enrollment::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'business_group_id' => $isB2B ? $corporateContractId : null,
                'user_id' => $userId,
                'course_id' => $courseId,
                'corporate_contract_id' => $corporateContractId,
                'mode' => $mode,
                'total_price_kopecks' => $totalPrice,
                'marketplace_share_kopecks' => $marketplaceShare,
                'teacher_share_kopecks' => $teacherShare,
                'status' => 'active',
                'progress_percent' => 0,
                'correlation_id' => $correlationId,
            ]);

            $milestones = $this->milestonePayment->createMilestoneSchedule($enrollment->id, $correlationId);

            $result = [
                'enrollment_id' => $enrollment->id,
                'enrollment_uuid' => $enrollment->uuid,
                'course_id' => $courseId,
                'mode' => $mode,
                'total_price_rub' => $totalPrice / 100,
                'marketplace_share_rub' => $marketplaceShare / 100,
                'teacher_share_rub' => $teacherShare / 100,
                'milestones' => $milestones,
                'status' => 'active',
            ];

            if ($idempotencyKey !== null) {
                $this->idempotency->record('education_enrollment', $idempotencyKey, [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ], $result, tenant()->id, 1440);
            }

            $this->audit->record('education_enrollment_created', 'Enrollment', $enrollment->id, [], [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'mode' => $mode,
                'total_price_rub' => $totalPrice / 100,
            ], $correlationId);

            Log::channel('audit')->info('Course enrollment created with split payment', [
                'correlation_id' => $correlationId,
                'enrollment_id' => $enrollment->id,
            ]);

            return $result;
        });
    }

    public function updateProgress(int $enrollmentId, int $progressPercent, string $correlationId): void
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        DB::transaction(function () use ($enrollment, $progressPercent, $correlationId) {
            $enrollment->update([
                'progress_percent' => min(100, $progressPercent),
            ]);

            if ($progressPercent >= 100) {
                $enrollment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            $this->audit->record('education_enrollment_progress_updated', 'Enrollment', $enrollment->id, [], [
                'correlation_id' => $correlationId,
                'enrollment_id' => $enrollment->id,
                'progress_percent' => $progressPercent,
            ], $correlationId);
        });
    }

    public function cancelEnrollment(int $enrollmentId, string $reason, string $correlationId): void
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        DB::transaction(function () use ($enrollment, $reason, $correlationId) {
            $enrollment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            DB::table('education_payment_milestones')
                ->where('enrollment_id', $enrollment->id)
                ->where('status', '!=', 'paid')
                ->update(['status' => 'cancelled']);

            $this->audit->record('education_enrollment_cancelled', 'Enrollment', $enrollment->id, [], [
                'correlation_id' => $correlationId,
                'enrollment_id' => $enrollment->id,
                'reason' => $reason,
            ], $correlationId);
        });
    }

    public function issueCertificate(int $enrollmentId, string $correlationId): array
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        if ($enrollment->status !== 'completed') {
            throw new \DomainException('Enrollment must be completed to issue certificate');
        }

        $certificateId = (string) Str::uuid();
        $certificateNumber = 'CERT-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');

        DB::transaction(function () use ($enrollment, $certificateId, $certificateNumber, $correlationId) {
            DB::table('education_certificates')->insert([
                'id' => $certificateId,
                'tenant_id' => $enrollment->tenant_id,
                'business_group_id' => $enrollment->business_group_id,
                'enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
                'certificate_number' => $certificateNumber,
                'issued_at' => now(),
                'valid_until' => now()->addYears(2),
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->audit->record('education_certificate_issued', 'Certificate', null, [], [
                'correlation_id' => $correlationId,
                'certificate_id' => $certificateId,
                'certificate_number' => $certificateNumber,
                'enrollment_id' => $enrollment->id,
            ], $correlationId);
        });

        return [
            'certificate_id' => $certificateId,
            'certificate_number' => $certificateNumber,
            'issued_at' => now()->toIso8601String(),
            'valid_until' => now()->addYears(2)->toIso8601String(),
        ];
    }
}
