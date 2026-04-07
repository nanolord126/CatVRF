<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class EnrollmentService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function enrollStudent(
            int $courseId,
            string $studentId,
            string $correlationId = '',
        ): Enrollment {

            try {
                $this->logger->info('Enrolling student in course', [
                    'course_id' => $courseId,
                    'student_id' => $studentId,
                    'correlation_id' => $correlationId,
                ]);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $enrollment = $this->db->transaction(function () use ($courseId, $studentId, $correlationId) {
                    $course = Course::findOrFail($courseId);

                    $commission = (int) ($course->price * 14 / 100);

                    $enrollment = Enrollment::create([
                        'tenant_id' => tenant()->id,
                        'course_id' => $courseId,
                        'student_id' => $studentId,
                        'status' => 'active',
                        'progress_percent' => 0,
                        'enrolled_at' => Carbon::now(),
                        'course_price' => $course->price,
                        'commission_price' => $commission,
                        'correlation_id' => $correlationId,
                    ]);

                    $course->increment('student_count');

                    EnrollmentCreated::dispatch($enrollment, $correlationId);

                    return $enrollment;
                });

                $this->logger->info('Student enrolled successfully', [
                    'enrollment_id' => $enrollment->id,
                    'correlation_id' => $correlationId,
                ]);

                return $enrollment;
            } catch (Throwable $e) {
                $this->logger->error('Enrollment failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function completeEnrollment(Enrollment $enrollment, string $correlationId = ''): Enrollment
        {

            try {
                $this->logger->info('Completing enrollment', [
                    'enrollment_id' => $enrollment->id,
                    'correlation_id' => $correlationId,
                ]);

                $enrollment->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::now(),
                    'progress_percent' => 100,
                ]);

                $this->logger->info('Enrollment completed', [
                    'enrollment_id' => $enrollment->id,
                    'correlation_id' => $correlationId,
                ]);

                return $enrollment;
            } catch (Throwable $e) {
                $this->logger->error('Failed to complete enrollment', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function dropEnrollment(Enrollment $enrollment, string $reason = '', string $correlationId = ''): bool
        {

            try {
                $this->logger->info('Dropping enrollment', [
                    'enrollment_id' => $enrollment->id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                $enrollment->update(['status' => 'dropped']);

                $this->logger->info('Enrollment dropped', [
                    'enrollment_id' => $enrollment->id,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            } catch (Throwable $e) {
                $this->logger->error('Failed to drop enrollment', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
}
