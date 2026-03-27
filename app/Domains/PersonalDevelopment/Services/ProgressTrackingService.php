<?php

declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Services;

use App\Domains\PersonalDevelopment\Models\Enrollment;
use App\Domains\PersonalDevelopment\Models\Milestone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * ProgressTrackingService — Production Ready 2026
 * 
 * Сервис управления прогрессом пользователей в программах саморазвития.
 * Реализовано для B2B (корпоративное развитие) и B2C (частные клиенты).
 */
final readonly class ProgressTrackingService
{
    /**
     * Конструктор с зависимостями.
     */
    public function __construct(
        private string $correlationId = ''
    ) {
        $this->correlationId = $this->correlationId ?: (string) Str::uuid();
    }

    /**
     * Создать контрольную точку прогресса (Milestone) для записи.
     */
    public function addMilestone(Enrollment $enrollment, string $title, string $requirements): Milestone
    {
        Log::channel('audit')->info('PD Progress: Adding new milestone', [
            'enrollment_uuid' => $enrollment->uuid,
            'title' => $title,
            'correlation_id' => $this->correlationId,
        ]);

        return Milestone::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $enrollment->tenant_id,
            'enrollment_id' => $enrollment->id,
            'title' => $title,
            'requirements' => $requirements,
            'is_completed' => false,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Завершить веху и обновить прогресс записи.
     */
    public function completeMilestone(Milestone $milestone): void
    {
        if ($milestone->is_completed) {
            return;
        }

        DB::transaction(function () use ($milestone) {
            // Помечаем веху выполненной
            $milestone->markAsCompleted();

            // Обновляем прогресс всей записи
            $milestone->enrollment->updateProgressFromMilestones();

            Log::channel('audit')->info('PD Progress: Milestone completed', [
                'milestone_uuid' => $milestone->uuid,
                'enrollment_uuid' => $milestone->enrollment->uuid,
                'new_progress' => $milestone->enrollment->progress_percent,
                'correlation_id' => $this->correlationId,
            ]);

            // Если прогресс 100%, помечаем запись как завершенную
            if ($milestone->enrollment->progress_percent >= 100) {
                $milestone->enrollment->update([
                    'status' => 'completed',
                    'correlation_id' => $this->correlationId,
                ]);

                Log::channel('audit')->info('PD Progress: Enrollment fully completed!', [
                    'enrollment_uuid' => $milestone->enrollment->uuid,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        });
    }

    /**
     * Получить подробный отчет о прогрессе пользователя.
     */
    public function getProgressReport(Enrollment $enrollment): array
    {
        $milestones = $enrollment->milestones()->orderBy('created_at')->get();
        
        return [
            'enrollment_uuid' => $enrollment->uuid,
            'title' => $enrollment->program?->title ?? $enrollment->course?->title,
            'status' => $enrollment->status,
            'total_progress' => $enrollment->progress_percent,
            'milestones' => $milestones->map(fn (Milestone $m) => [
                'uuid' => $m->uuid,
                'title' => $m->title,
                'is_completed' => $m->is_completed,
                'completed_at' => $m->completed_at?->format('Y-m-d H:i:s'),
            ]),
            'correlation_id' => $this->correlationId,
        ];
    }
}
