<?php

declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Auto\Events\CarImportCalculatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;

final class SendImportCalculationNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly NotificationService $notificationService,
        private readonly LogManager $log,) {}

    public function handle(CarImportCalculatedEvent $event): void
    {
        $totalDuties = $event->calculationData['total_duties']['amount_rub'] ?? 0;
        $restrictionsCount = count($event->calculationData['restrictions'] ?? []);

        $title = 'Расчет растаможки готов';
        $message = "VIN: {$event->vin}, Общая сумма пошлин: {$totalDuties} RUB";

        if ($restrictionsCount > 0) {
            $message .= " (Обнаружено ограничений: {$restrictionsCount})";
        }

        $this->notificationService->send(
            userId: $event->userId,
            title: $title,
            message: $message,
            type: 'car_import_calculation',
            data: [
                'vin' => $event->vin,
                'correlation_id' => $event->correlationId,
                'total_duties_rub' => $totalDuties,
            ],
        );

        $this->log->channel('audit')->$this->logger->info('car.import_calculation.notification.sent', [
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'vin' => $event->vin,
        ]);
    }
}
