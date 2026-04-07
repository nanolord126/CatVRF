<?php

declare(strict_types=1);

namespace App\Domains\Food\Infrastructure\Gateways;

use Carbon\Carbon;

use App\Shared\Domain\ValueObjects\Uuid;
use Psr\Log\LoggerInterface;

/**
 * This is a fake gateway for demonstration purposes.
 * In a real application, this would interact with an external Delivery Service API.
 */
final readonly class FakeDeliveryServiceGateway
{
    public function __construct(private LoggerInterface $logger)
    {

    }

    /**
     * @param Uuid $orderId
     * @param string $deliveryAddress
     * @param Uuid|null $correlationId
     * @return array<string, mixed>
     */
    public function scheduleDelivery(Uuid $orderId, string $deliveryAddress, ?Uuid $correlationId = null): array
    {
        $this->logger->info('Scheduling delivery for order.', [
            'order_id' => $orderId->toString(),
            'address' => $deliveryAddress,
            'correlation_id' => $correlationId?->toString(),
        ]);

        // Simulate API call to a delivery service
        $deliveryId = Uuid::create();
        $estimatedTimeMinutes = random_int(25, 60);

        $this->logger->info('Delivery scheduled successfully.', [
            'order_id' => $orderId->toString(),
            'delivery_id' => $deliveryId->toString(),
            'estimated_time_minutes' => $estimatedTimeMinutes,
            'correlation_id' => $correlationId?->toString(),
        ]);

        return [
            'success' => true,
            'delivery_id' => $deliveryId->toString(),
            'estimated_time_minutes' => $estimatedTimeMinutes,
            'status' => 'scheduled',
        ];
    }

    /**
     * @param Uuid $deliveryId
     * @param Uuid|null $correlationId
     * @return array<string, mixed>
     */
    public function getDeliveryStatus(Uuid $deliveryId, ?Uuid $correlationId = null): array
    {
        $this->logger->info('Fetching delivery status.', [
            'delivery_id' => $deliveryId->toString(),
            'correlation_id' => $correlationId?->toString(),
        ]);

        $statuses = ['scheduled', 'in_transit', 'delivered', 'failed'];
        $status = $statuses[array_rand($statuses)];

        return [
            'delivery_id' => $deliveryId->toString(),
            'status' => $status,
            'updated_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
