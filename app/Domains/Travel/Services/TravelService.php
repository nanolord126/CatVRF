<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TravelService
{

    private readonly string $correlationId;


    public function __construct(private readonly FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {
            $this->correlationId = $correlationId ?: Str::uuid()->toString();
        }

        public function bookTour(int $tourId, int $seats): array
        {

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($tourId, $seats) {
                $tour = TravelTour::lockForUpdate()->find($tourId);

                if (!$tour || ($tour->booked + $seats) > $tour->capacity) {
                    throw new \DomainException('Tour is fully booked');
                }

                $tour->update(['booked' => $tour->booked + $seats]);

                $this->logger->info('Tour booked', [
                    'correlation_id' => $this->correlationId,
                    'tour_id' => $tourId,
                    'seats' => $seats,
                ]);

                return ['success' => true, 'tour' => $tour];
            });
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
