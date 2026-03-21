<?php declare(strict_types=1);

namespace App\Domains\Courses\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Courses\Models\Enrollment;
use App\Domains\Courses\Models\Course;
use App\Domains\Courses\Events\EnrollmentCreated;
use Illuminate\Support\Facades\DB;
use Throwable;

final class EnrollmentService
{
    public function enrollStudent(
        int $courseId,
        string $studentId,
        string $correlationId = '',
    ): Enrollment {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'enrollStudent'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL enrollStudent', ['domain' => __CLASS__]);

        try {
            Log::channel('audit')->info('Enrolling student in course', [
                'course_id' => $courseId,
                'student_id' => $studentId,
                'correlation_id' => $correlationId,
            ]);

            $enrollment = DB::transaction(function () use ($courseId, $studentId, $correlationId) {
                $course = Course::findOrFail($courseId);

                $commission = (int) ($course->price * 14 / 100);

                $enrollment = Enrollment::create([
                    'tenant_id' => tenant('id'),
                    'course_id' => $courseId,
                    'student_id' => $studentId,
                    'status' => 'active',
                    'progress_percent' => 0,
                    'enrolled_at' => now(),
                    'course_price' => $course->price,
                    'commission_price' => $commission,
                    'correlation_id' => $correlationId,
                ]);

                $course->increment('student_count');

                EnrollmentCreated::dispatch($enrollment, $correlationId);

                return $enrollment;
            });

            Log::channel('audit')->info('Student enrolled successfully', [
                'enrollment_id' => $enrollment->id,
                'correlation_id' => $correlationId,
            ]);

            return $enrollment;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Enrollment failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function completeEnrollment(Enrollment $enrollment, string $correlationId = ''): Enrollment
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'completeEnrollment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeEnrollment', ['domain' => __CLASS__]);

        try {
            Log::channel('audit')->info('Completing enrollment', [
                'enrollment_id' => $enrollment->id,
                'correlation_id' => $correlationId,
            ]);

            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'progress_percent' => 100,
            ]);

            Log::channel('audit')->info('Enrollment completed', [
                'enrollment_id' => $enrollment->id,
                'correlation_id' => $correlationId,
            ]);

            return $enrollment;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to complete enrollment', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function dropEnrollment(Enrollment $enrollment, string $reason = '', string $correlationId = ''): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'dropEnrollment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL dropEnrollment', ['domain' => __CLASS__]);

        try {
            Log::channel('audit')->info('Dropping enrollment', [
                'enrollment_id' => $enrollment->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $enrollment->update(['status' => 'dropped']);

            Log::channel('audit')->info('Enrollment dropped', [
                'enrollment_id' => $enrollment->id,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to drop enrollment', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
