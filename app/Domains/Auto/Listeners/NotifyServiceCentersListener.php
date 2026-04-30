<?php

declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Auto\Events\ServiceOrderCreatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;

final class NotifyServiceCentersListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly NotificationService $notificationService,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    public function handle(ServiceOrderCreatedEvent $event): void
    {
        $vehicle = $this->db->table('auto_vehicles')
            ->where('id', $event->order->vehicle_id)
            ->first();

        if ($vehicle === null) {
            return;
        }

        $nearestServices = $this->db->table('auto_services')
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

        $this->log->channel('audit')->$this->logger->info('auto.service_centers.notified', [
            'correlation_id' => $event->correlationId,
            'order_id' => $event->order->id,
            'services_count' => $nearestServices->count(),
        ]);
    }
}
