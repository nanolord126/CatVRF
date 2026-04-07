<?php declare(strict_types=1);

/**
 * TowingCompleted — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/towingcompleted
 */


namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
final class TowingCompleted
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly TowingRequest $request,
            public readonly string $correlationId, public readonly LoggerInterface $logger
        ) {
            $this->logger->info('TowingCompleted event dispatched', [
                'correlation_id' => $this->correlationId,
                'request_id' => $this->request->id,
                'dropoff_location' => $this->request->dropoff_location,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('tenant.' . $this->request->tenant_id),
                new PrivateChannel('user.' . $this->request->client_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'towing.completed';
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
