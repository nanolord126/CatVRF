<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\CarImportCalculatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class SendImportCalculationNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

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

        Log::channel('audit')->info('car.import_calculation.notification.sent', [
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'vin' => $event->vin,
        ]);
    }
}
