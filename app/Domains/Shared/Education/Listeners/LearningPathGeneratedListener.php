<?php

declare(strict_types=1);

namespace App\Domains\Education\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Education\Events\LearningPathGeneratedEvent;
use App\Services\AuditService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

final readonly class LearningPathGeneratedListener
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly LogManager $log,
        private readonly HttpFactory $http,) {}

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

        $this->log->channel('audit')->$this->logger->info('Learning path synced to CRM', [
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
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];

        $webhookUrl = config('services.crm.webhook_url');

        if ($webhookUrl !== null) {
            try {
                $this->http->timeout(10)->post($webhookUrl, $crmData);
            } catch (\Exception $e) {
                $this->log->channel('audit')->error('CRM sync failed', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
