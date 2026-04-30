<?php

declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Auto\Events\CarImportInitiatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;

final class NotifyCustomsDepartmentListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly NotificationService $notificationService,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    public function handle(CarImportInitiatedEvent $event): void
    {
        $import = $this->db->table('car_imports')
            ->where('id', $event->importId)
            ->first();

        if ($import === null) {
            return;
        }

        $this->notificationService->sendToBusiness(
            businessGroupId: $import->business_group_id ?? 1,
            title: 'Новый запрос на импорт авто',
            message: "VIN: {$event->vin}, Страна: {$import->country_origin}",
            type: 'new_car_import',
            data: [
                'import_id' => $event->importId,
                'vin' => $event->vin,
                'correlation_id' => $event->correlationId,
            ],
        );

        $this->log->channel('audit')->$this->logger->info('car.import.customs.notified', [
            'correlation_id' => $event->correlationId,
            'import_id' => $event->importId,
            'vin' => $event->vin,
        ]);
    }
}
