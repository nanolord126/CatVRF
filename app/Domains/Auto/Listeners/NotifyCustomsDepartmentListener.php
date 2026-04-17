<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\CarImportInitiatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class NotifyCustomsDepartmentListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(CarImportInitiatedEvent $event): void
    {
        $import = DB::table('car_imports')
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

        Log::channel('audit')->info('car.import.customs.notified', [
            'correlation_id' => $event->correlationId,
            'import_id' => $event->importId,
            'vin' => $event->vin,
        ]);
    }
}
