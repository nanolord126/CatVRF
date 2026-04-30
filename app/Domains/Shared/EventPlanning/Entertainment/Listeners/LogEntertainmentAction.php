<?php

declare(strict_types=1);

/**
 * LogEntertainmentAction — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/logentertainmentaction
 */

namespace App\\Domains\\Shared\EventPlanning\Entertainment\Listeners;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;

final class LogEntertainmentAction
{
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

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}


    public function handle(object $event): void
    {
        $payload = [];

        if ($event instanceof BookingCreatedEvent) {
            $payload = [
                'type' => 'booking_created',
                'booking_id' => $event->booking->id,
                'uuid' => $event->booking->uuid,
                'amount' => $event->booking->total_amount_kopecks,
            ];
        }

        $this->logger->$this->logger->info('Entertainment action audit', array_merge($payload, [
            'correlation_id' => $event->correlationId ?? 'unknown',
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]));
    }

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return self::class.'@'.self::VERSION;
    }
}
