<?php declare(strict_types=1);

namespace App\Domains\Courses\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Courses\Models\Enrollment;
use App\Domains\Courses\Models\LessonProgress;
use App\Domains\Courses\Events\LessonCompleted;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProgressTrackingService
{
    public function trackLessonWatch(
        int $enrollmentId,
        int $lessonId,
        int $watchTimeSeconds,
        string $correlationId = '',
    ): LessonProgress {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'trackLessonWatch'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL trackLessonWatch', ['domain' => __CLASS__]);

        try {
            Log::channel('audit')->info('Tracking lesson watch time', [
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
                'watch_time' => $watchTimeSeconds,
                'correlation_id' => $correlationId,
            ]);

            $progress = DB::transaction(function () use ($enrollmentId, $lessonId, $watchTimeSeconds, $correlationId) {
                $progress = LessonProgress::firstOrCreate([
                    'tenant_id' => tenant('id'),
                    'enrollment_id' => $enrollmentId,
                    'lesson_id' => $lessonId,
                ]);

                $progress->update([
                    'watch_time_seconds' => $progress->watch_time_seconds + $watchTimeSeconds,
                    'completion_percent' => min(100, ($progress->watch_time_seconds + $watchTimeSeconds) / 600 * 100),
                ]);

                // Update enrollment total watch time
                Enrollment::findOrFail($enrollmentId)->update([
                    'total_watch_time_seconds' => DB::raw("total_watch_time_seconds + {$watchTimeSeconds}"),
                    'last_accessed_at' => now(),
                ]);

                return $progress;
            });

            Log::channel('audit')->info('Lesson watch time tracked', [
                'progress_id' => $progress->id,
                'correlation_id' => $correlationId,
            ]);

            return $progress;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to track lesson watch time', [
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'markLessonComplete'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL markLessonComplete', ['domain' => __CLASS__]);

        try {
            Log::channel('audit')->info('Marking lesson as complete', [
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
                'correlation_id' => $correlationId,
            ]);

            $progress = DB::transaction(function () use ($enrollmentId, $lessonId, $correlationId) {
                $progress = LessonProgress::where('enrollment_id', $enrollmentId)
                    ->where('lesson_id', $lessonId)
                    ->firstOrFail();

                $progress->update([
                    'is_completed' => true,
                    'completed_at' => now(),
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

            Log::channel('audit')->info('Lesson marked as complete', [
                'progress_id' => $progress->id,
                'correlation_id' => $correlationId,
            ]);

            return $progress;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to mark lesson as complete', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function getEnrollmentProgress(int $enrollmentId, string $correlationId = ''): array
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'getEnrollmentProgress'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getEnrollmentProgress', ['domain' => __CLASS__]);

        try {
            Log::channel('audit')->info('Getting enrollment progress', [
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
            Log::channel('audit')->error('Failed to get enrollment progress', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
