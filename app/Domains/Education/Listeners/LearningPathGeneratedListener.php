<?php declare(strict_types=1);

namespace App\Domains\Education\Listeners;

use App\Domains\Education\Events\LearningPathGeneratedEvent;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

final readonly class LearningPathGeneratedListener
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function handle(LearningPathGeneratedEvent $event): void
    {
        $this->audit->log('education_learning_path_crm_sync', [
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->tenantId,
            'user_id' => $event->userId,
            'course_id' => $event->courseId,
            'business_group_id' => $event->businessGroupId,
            'path_id' => $event->recommendation->pathId,
            'estimated_hours' => $event->recommendation->estimatedHours,
            'completion_probability' => $event->recommendation->completionProbability,
            'difficulty_level' => $event->recommendation->difficultyLevel,
        ]);

        Log::channel('audit')->info('Learning path synced to CRM', [
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'course_id' => $event->courseId,
        ]);

        $this->sendToCRM($event);
    }

    private function sendToCRM(LearningPathGeneratedEvent $event): void
    {
        $crmData = [
            'event' => 'learning_path_generated',
            'user_id' => $event->userId,
            'course_id' => $event->courseId,
            'tenant_id' => $event->tenantId,
            'business_group_id' => $event->businessGroupId,
            'path_id' => $event->recommendation->pathId,
            'estimated_hours' => $event->recommendation->estimatedHours,
            'estimated_weeks' => $event->recommendation->estimatedWeeks,
            'difficulty_level' => $event->recommendation->difficultyLevel,
            'completion_probability' => $event->recommendation->completionProbability,
            'milestones_count' => count($event->recommendation->milestones),
            'modules_count' => count($event->recommendation->modules),
            'correlation_id' => $event->correlationId,
            'timestamp' => now()->toIso8601String(),
        ];

        $webhookUrl = config('services.crm.webhook_url');

        if ($webhookUrl !== null) {
            try {
                \Illuminate\Support\Facades\Http::timeout(10)->post($webhookUrl, $crmData);
            } catch (\Exception $e) {
                Log::channel('audit')->error('CRM sync failed', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
