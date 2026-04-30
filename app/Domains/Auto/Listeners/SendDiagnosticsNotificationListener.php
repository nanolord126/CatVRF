<?php

declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Auto\Events\AIDiagnosticsCompletedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;

final class SendDiagnosticsNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly NotificationService $notificationService,
        private readonly LogManager $log,) {}

    public function handle(AIDiagnosticsCompletedEvent $event): void
    {
        $damageCount = count($event->diagnosticsData['damage_detection']['damages'] ?? []);
        $criticalCount = $event->diagnosticsData['damage_detection']['critical_count'] ?? 0;

        $title = 'Диагностика авто завершена';
        $message = "Обнаружено повреждений: $damageCount (критических: $criticalCount)";

        $this->notificationService->send(
            userId: $event->userId,
            title: $title,
            message: $message,
            type: 'auto_diagnostics',
            data: [
                'vehicle_id' => $event->vehicle->id,
                'vehicle_uuid' => $event->vehicle->uuid,
                'correlation_id' => $event->correlationId,
            ],
        );

        $this->log->channel('audit')->$this->logger->info('auto.diagnostics_notification.sent', [
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'vehicle_id' => $event->vehicle->id,
        ]);
    }
}
