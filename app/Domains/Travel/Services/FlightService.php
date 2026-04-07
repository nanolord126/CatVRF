<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class FlightService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function bookFlight(
            TravelFlight $flight,
            string $correlationId = null
    ): TravelFlight {

            $correlationId ??= Str::uuid()->toString();

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($flight, $correlationId) {
                    $flight->lockForUpdate();

                    if ($flight->available_seats <= 0) {
                        throw new \RuntimeException('No available seats on this flight');
                    }

                    $flight->decrement('available_seats');

                    $this->logger->info('Flight booked', [
                        'flight_id' => $flight->id,
                        'flight_number' => $flight->flight_number,
                        'remaining_seats' => $flight->available_seats,
                        'commission_amount' => $flight->commission_amount,
                        'correlation_id' => $correlationId,
                        'timestamp' => now(),
                    ]);

                    FlightBooked::dispatch($flight, $correlationId);

                    return $flight->refresh();
                });
            } catch (Throwable $e) {
                $this->logger->error('Flight booking failed', [
                    'flight_id' => $flight->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        public function releaseFlight(
            TravelFlight $flight,
            string $correlationId = null
    ): TravelFlight {

            $correlationId ??= $flight->correlation_id ?? Str::uuid()->toString();

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($flight, $correlationId) {
                    $flight->lockForUpdate();

                    $flight->increment('available_seats');

                    $this->logger->info('Flight seat released', [
                        'flight_id' => $flight->id,
                        'flight_number' => $flight->flight_number,
                        'available_seats' => $flight->available_seats,
                        'correlation_id' => $correlationId,
                        'timestamp' => now(),
                    ]);

                    return $flight->refresh();
                });
            } catch (Throwable $e) {
                $this->logger->error('Flight seat release failed', [
                    'flight_id' => $flight->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
