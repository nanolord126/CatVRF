<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Application\UseCases;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Delivery\Domain\Entities\Delivery;
use App\Domains\Delivery\Domain\Enums\DeliveryStatus;
use App\Domains\Delivery\Domain\Events\DeliveryStatusChanged;
use App\Domains\Delivery\Domain\Repositories\DeliveryRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;

/**
 * Class UpdateDeliveryStatusUseCase
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 */
final class UpdateDeliveryStatusUseCase
{
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DeliveryRepositoryInterface $deliveryRepository,
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {}

    public function __invoke(string $deliveryId, DeliveryStatus $status, ?string $correlationId = null): Delivery
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->logger->$this->logger->info('Updating delivery status', [
            'correlation_id' => $correlationId,
            'delivery_id' => $deliveryId,
            'status' => $status->value,
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($deliveryId, $status, $correlationId) {
            $this->deliveryRepository->update($deliveryId, ['status' => $status]);

            $delivery = $this->deliveryRepository->findById($deliveryId);

            $this->eventDispatcher->dispatch(new DeliveryStatusChanged($delivery, $correlationId));

            return $delivery;
        });
    }
}
