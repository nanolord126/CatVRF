<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TransportationService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function bookTransportation(
            TravelTransportation $transportation,
            int $seatsRequired = 1,
            string $correlationId = null
    ): TravelTransportation {

            $correlationId ??= Str::uuid()->toString();

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use (
                    $transportation,
                    $seatsRequired,
                    $correlationId
    ) {
                    $transportation->lockForUpdate();

                    if ($transportation->available_count < $seatsRequired) {
                        throw new \RuntimeException('Not enough available spaces for transportation');
                    }

                    $transportation->decrement('available_count', $seatsRequired);

                    $this->logger->info('Transportation booked', [
                        'transportation_id' => $transportation->id,
                        'type' => $transportation->type,
                        'seats_booked' => $seatsRequired,
                        'remaining_spaces' => $transportation->available_count,
                        'commission_amount' => $transportation->commission_amount,
                        'correlation_id' => $correlationId,
                        'timestamp' => now(),
                    ]);

                    TransportationBooked::dispatch($transportation, $correlationId);

                    return $transportation->refresh();
                });
            } catch (Throwable $e) {
                $this->logger->error('Transportation booking failed', [
                    'transportation_id' => $transportation->id,
                    'seats_required' => $seatsRequired,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        public function releaseTransportation(
            TravelTransportation $transportation,
            int $seatsToRelease = 1,
            string $correlationId = null
    ): TravelTransportation {

            $correlationId ??= $transportation->correlation_id ?? Str::uuid()->toString();

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use (
                    $transportation,
                    $seatsToRelease,
                    $correlationId
    ) {
                    $transportation->lockForUpdate();

                    $newAvailable = $transportation->available_count + $seatsToRelease;

                    if ($newAvailable > $transportation->capacity) {
                        throw new \RuntimeException('Cannot release more spaces than transportation capacity');
                    }

                    $transportation->increment('available_count', $seatsToRelease);

                    $this->logger->info('Transportation spaces released', [
                        'transportation_id' => $transportation->id,
                        'type' => $transportation->type,
                        'spaces_released' => $seatsToRelease,
                        'available_spaces' => $transportation->available_count,
                        'correlation_id' => $correlationId,
                        'timestamp' => now(),
                    ]);

                    return $transportation->refresh();
                });
            } catch (Throwable $e) {
                $this->logger->error('Transportation space release failed', [
                    'transportation_id' => $transportation->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
