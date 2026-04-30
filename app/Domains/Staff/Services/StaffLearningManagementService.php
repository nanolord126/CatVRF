<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Entities\StaffCourse;
use App\Domains\Staff\Domain\Entities\StaffCourseEnrollment;
use App\Domains\Staff\Domain\Entities\StaffCertification;
use App\Domains\Staff\Domain\Entities\StaffDevelopmentPlan;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffLearningManagementService — сервис управления обучением сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Управляет курсами, сертификациями, планами развития, навыками.
 */
final class StaffLearningManagementService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Записывает сотрудника на курс.
     */
    public function enrollInCourse(int $staffId, int $courseId): StaffCourseEnrollment
    {
        $fraudResult = $this->fraudControl->checkRequest([
            'action' => 'staff_learning_enroll',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);
        $course = StaffCourse::findOrFail($courseId);

        $existing = StaffCourseEnrollment::where('staff_id', $staffId)
            ->where('course_id', $courseId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $enrollment = StaffCourseEnrollment::create([
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staffId,
            'course_id' => $courseId,
            'progress' => 0,
            'modules_completed' => 0,
            'total_modules' => count($course->modules ?? []),
            'time_spent_minutes' => 0,
            'started_at' => now(),
            'completed_at' => null,
            'due_date' => now()->addDays($course->duration_minutes / 60 / 8), // ~8 hours work day
            'status' => 'in_progress',
            'score' => null,
            'feedback' => null,
        ]);

        Cache::tags(['staff_learning', 'staff:' . $staffId])->flush();

        $this->logAction('course_enrolled', [
            'entity_type' => 'staff_course_enrollment',
            'entity_id' => $enrollment->id,
            'staff_id' => $staffId,
            'course_id' => $courseId,
        ]);

        return $enrollment;
    }

    /**
     * Обновляет прогресс по курсу.
     */
    public function updateCourseProgress(int $enrollmentId, float $progress, int $timeSpentMinutes): void
    {
        $enrollment = StaffCourseEnrollment::findOrFail($enrollmentId);

        $enrollment->update([
            'progress' => min(100, $progress),
            'time_spent_minutes' => $enrollment->time_spent_minutes + $timeSpentMinutes,
        ]);

        if ($progress >= 100 && $enrollment->status !== 'completed') {
            $this->completeCourse($enrollmentId);
        }

        Cache::tags(['staff_learning', 'staff:' . $enrollment->staff_id])->flush();
    }

    /**
     * Завершает курс.
     */
    public function completeCourse(int $enrollmentId, ?float $score = null, ?string $feedback = null): StaffCourseEnrollment
    {
        $enrollment = StaffCourseEnrollment::findOrFail($enrollmentId);

        if ($enrollment->status === 'completed') {
            return $enrollment;
        }

        $enrollment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'score' => $score,
            'feedback' => $feedback,
        ]);

        // Начисляем очки за завершение курса
        $course = $enrollment->course;
        if ($course->price > 0) {
            app(StaffGamificationService::class)->awardPoints(
                $enrollment->staff_id,
                (int) $course->price,
                'course_completed',
                "Completed course: {$course->title}",
            );
        }

        Cache::tags(['staff_learning', 'staff:' . $enrollment->staff_id])->flush();

        $this->logAction('course_completed', [
            'entity_type' => 'staff_course_enrollment',
            'entity_id' => $enrollmentId,
            'staff_id' => $enrollment->staff_id,
            'score' => $score,
        ]);

        return $enrollment->fresh();
    }

    /**
     * Добавляет сертификацию сотруднику.
     */
    public function addCertification(array $data): StaffCertification
    {
        $fraudResult = $this->fraudControl->checkRequest([
            'action' => 'staff_learning_certification_add',
            'staff_id' => $data['staff_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $certification = StaffCertification::create([
            'tenant_id' => $data['tenant_id'],
            'staff_id' => $data['staff_id'],
            'name' => $data['name'],
            'issuing_organization' => $data['issuing_organization'],
            'credential_id' => $data['credential_id'] ?? null,
            'issued_date' => $data['issued_date'],
            'expiry_date' => $data['expiry_date'] ?? null,
            'certificate_url' => $data['certificate_url'] ?? null,
            'certificate_path' => $data['certificate_path'] ?? null,
            'is_verified' => $data['is_verified'] ?? false,
            'verified_at' => $data['verified_at'] ?? null,
        ]);

        Cache::tags(['staff_learning', 'staff:' . $data['staff_id']])->flush();

        $this->logCreated('staff_certification', $certification->id, [
            'staff_id' => $data['staff_id'],
            'name' => $data['name'],
        ]);

        return $certification;
    }

    /**
     * Создаёт план развития сотрудника.
     */
    public function createDevelopmentPlan(array $data): StaffDevelopmentPlan
    {
        $fraudResult = $this->fraudControl->checkRequest([
            'action' => 'staff_learning_development_plan',
            'staff_id' => $data['staff_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $plan = StaffDevelopmentPlan::create([
            'tenant_id' => $data['tenant_id'],
            'staff_id' => $data['staff_id'],
            'manager_id' => $data['manager_id'] ?? null,
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? now()->addMonths(6),
            'goals' => $data['goals'] ?? [],
            'objectives' => $data['objectives'] ?? [],
            'development_activities' => $data['development_activities'] ?? [],
            'status' => 'active',
            'progress' => 0,
            'manager_notes' => $data['manager_notes'] ?? null,
            'employee_notes' => $data['employee_notes'] ?? null,
        ]);

        Cache::tags(['staff_learning', 'staff:' . $data['staff_id']])->flush();

        $this->logCreated('staff_development_plan', $plan->id, [
            'staff_id' => $data['staff_id'],
        ]);

        return $plan;
    }

    /**
     * Получает курсы сотрудника.
     */
    public function getStaffCourses(int $staffId): array
    {
        $cacheKey = "staff_courses:{$staffId}";

        return Cache::tags(['staff_learning', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($staffId) {
                return StaffCourseEnrollment::where('staff_id', $staffId)
                    ->with('course')
                    ->latest()
                    ->get()
                    ->map(fn ($e) => [
                        'id' => $e->id,
                        'course_name' => $e->course->title,
                        'progress' => $e->progress,
                        'status' => $e->status,
                        'started_at' => $e->started_at->toDateString(),
                        'completed_at' => $e->completed_at?->toDateString(),
                        'score' => $e->score,
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * Получает сертификаты сотрудника.
     */
    public function getStaffCertifications(int $staffId): array
    {
        $cacheKey = "staff_certifications:{$staffId}";

        return Cache::tags(['staff_learning', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(12),
            function () use ($staffId) {
                return StaffCertification::where('staff_id', $staffId)
                    ->latest('issued_date')
                    ->get()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'issuing_organization' => $c->issuing_organization,
                        'issued_date' => $c->issued_date->toDateString(),
                        'expiry_date' => $c->expiry_date?->toDateString(),
                        'is_verified' => $c->is_verified,
                        'is_valid' => $c->is_valid,
                        'is_expiring' => $c->is_expiring,
                        'is_expired' => $c->is_expired,
                    ])
                    ->toArray();
            },
        );
    }
}
