<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\ServiceOrderCreatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class NotifyServiceCentersListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(ServiceOrderCreatedEvent $event): void
    {
        $vehicle = DB::table('auto_vehicles')
            ->where('id', $event->order->vehicle_id)
            ->first();

        if ($vehicle === null) {
            return;
        }

        $nearestServices = DB::table('auto_services')
            ->where('tenant_id', $event->tenantId)
            ->where('is_active', true)
            ->limit(5)
            ->get();

        foreach ($nearestServices as $service) {
            $this->notificationService->sendToBusiness(
                businessGroupId: $service->business_group_id,
                title: 'Новый заказ на ремонт',
                message: "VIN: {$vehicle->vin}, Сумма: {$event->order->total_price} RUB",
                type: 'new_repair_order',
                data: [
                    'order_id' => $event->order->id,
                    'order_uuid' => $event->order->uuid,
                    'vehicle_vin' => $vehicle->vin,
                    'correlation_id' => $event->correlationId,
                ],
            );
        }

        Log::channel('audit')->info('auto.service_centers.notified', [
            'correlation_id' => $event->correlationId,
            'order_id' => $event->order->id,
            'services_count' => $nearestServices->count(),
        ]);
    }
}
