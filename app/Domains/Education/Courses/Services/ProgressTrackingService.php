<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ProgressTrackingService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function trackLessonWatch(
            int $enrollmentId,
            int $lessonId,
            int $watchTimeSeconds,
            string $correlationId = '',
        ): LessonProgress {

            try {
                $this->logger->info('Tracking lesson watch time', [
                    'enrollment_id' => $enrollmentId,
                    'lesson_id' => $lessonId,
                    'watch_time' => $watchTimeSeconds,
                    'correlation_id' => $correlationId,
                ]);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $progress = $this->db->transaction(function () use ($enrollmentId, $lessonId, $watchTimeSeconds, $correlationId) {
                    $progress = LessonProgress::firstOrCreate([
                        'tenant_id' => tenant()->id,
                        'enrollment_id' => $enrollmentId,
                        'lesson_id' => $lessonId,
                    ]);

                    $progress->update([
                        'watch_time_seconds' => $progress->watch_time_seconds + $watchTimeSeconds,
                        'completion_percent' => min(100, ($progress->watch_time_seconds + $watchTimeSeconds) / 600 * 100),
                    ]);

                    // Update enrollment total watch time
                    Enrollment::findOrFail($enrollmentId)->update([
                        'total_watch_time_seconds' => $this->db->raw("total_watch_time_seconds + {$watchTimeSeconds}"),
                        'last_accessed_at' => Carbon::now(),
                    ]);

                    return $progress;
                });

                $this->logger->info('Lesson watch time tracked', [
                    'progress_id' => $progress->id,
                    'correlation_id' => $correlationId,
                ]);

                return $progress;
            } catch (Throwable $e) {
                $this->logger->error('Failed to track lesson watch time', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function markLessonComplete(
            int $enrollmentId,
            int $lessonId,
            string $correlationId = '',
        ): LessonProgress {

            try {
                $this->logger->info('Marking lesson as complete', [
                    'enrollment_id' => $enrollmentId,
                    'lesson_id' => $lessonId,
                    'correlation_id' => $correlationId,
                ]);

                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $progress = $this->db->transaction(function () use ($enrollmentId, $lessonId, $correlationId) {
                    $progress = LessonProgress::where('enrollment_id', $enrollmentId)
                        ->where('lesson_id', $lessonId)
                        ->firstOrFail();

                    $progress->update([
                        'is_completed' => true,
                        'completed_at' => Carbon::now(),
                        'completion_percent' => 100,
                    ]);

                    // Calculate enrollment progress
                    $enrollment = Enrollment::findOrFail($enrollmentId);
                    $totalLessons = $enrollment->course->lessons()->count();
                    $completedLessons = LessonProgress::where('enrollment_id', $enrollmentId)
                        ->where('is_completed', true)
                        ->count();

                    $progressPercent = $totalLessons > 0 ? (int) ($completedLessons / $totalLessons * 100) : 0;
                    $enrollment->update(['progress_percent' => $progressPercent]);

                    LessonCompleted::dispatch($progress, $correlationId);

                    return $progress;
                });

                $this->logger->info('Lesson marked as complete', [
                    'progress_id' => $progress->id,
                    'correlation_id' => $correlationId,
                ]);

                return $progress;
            } catch (Throwable $e) {
                $this->logger->error('Failed to mark lesson as complete', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function getEnrollmentProgress(int $enrollmentId, string $correlationId = ''): array
        {

            try {
                $this->logger->info('Getting enrollment progress', [
                    'enrollment_id' => $enrollmentId,
                    'correlation_id' => $correlationId,
                ]);

                $enrollment = Enrollment::findOrFail($enrollmentId);
                $lessons = $enrollment->course->lessons()->count();
                $completedLessons = $enrollment->lessonProgress()
                    ->where('is_completed', true)
                    ->count();

                return [
                    'enrollment_id' => $enrollmentId,
                    'total_lessons' => $lessons,
                    'completed_lessons' => $completedLessons,
                    'progress_percent' => $enrollment->progress_percent,
                    'watch_time_hours' => $enrollment->total_watch_time_seconds / 3600,
                    'status' => $enrollment->status,
                ];
            } catch (Throwable $e) {
                $this->logger->error('Failed to get enrollment progress', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
}
