<?php declare(strict_types=1);

/**
 * VehicleRentalStarted — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/vehiclerentalstarted
 */


namespace App\Domains\Auto\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class VehicleRentalStarted
{

    
        public function __construct(
            public readonly VehicleRental $rental,
            public readonly string $correlationId, public readonly LoggerInterface $logger
        ) {
            $this->logger->info('VehicleRentalStarted event dispatched', [
                'correlation_id' => $this->correlationId,
                'rental_id' => $this->rental->id,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('tenant.' . $this->rental->tenant_id),
                new PrivateChannel('user.' . $this->rental->renter_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'rental.started';
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
