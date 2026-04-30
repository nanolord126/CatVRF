<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Interfaces\Jobs;

use Psr\Log\LoggerInterface;
use App\Domains\Delivery\Application\UseCases\UpdateDeliveryStatusUseCase;
use App\Domains\Delivery\Domain\Enums\DeliveryStatus;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class ProcessDeliveryJob
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ProcessDeliveryJob implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(
        private readonly string $deliveryId,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {}

    public function handle(UpdateDeliveryStatusUseCase $updateDeliveryStatusUseCase): void
    {
        $this->logger->$this->logger->info('Processing delivery job', [
            'correlation_id' => $this->correlationId,
            'delivery_id' => $this->deliveryId,
        ]);

        // Some delivery processing logic here...

        $updateDeliveryStatusUseCase(
            $this->deliveryId,
            DeliveryStatus::DELIVERED,
            $this->correlationId
        );
    }

    public function tags(): array
    {
        return ['delivery', 'delivery-id:'.$this->deliveryId];
    }
}
