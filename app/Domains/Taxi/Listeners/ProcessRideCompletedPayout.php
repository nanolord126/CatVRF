<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Listeners;

use Carbon\CarbonImmutable;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;

final class ProcessRideCompletedPayout
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard
    ) {}


    public function handle(RideCompleted $event): void
    {
        try {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () use ($event) {
                // Update driver wallet with ride earnings
                $this->logger->$this->logger->info('Ride payout processed', [
                    'ride_id' => $event->rideId,
                    'driver_id' => $event->driverId,
                    'amount' => $event->priceAmount,
                    'correlation_id' => $event->correlationId,
                    'action' => 'ride_completed_payout',
                ]);
                // WalletService::credit($driver_id, $event->priceAmount)
            });
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process ride payout', [
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
