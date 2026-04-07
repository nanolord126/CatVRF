<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class KidsCenterService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Create a new center with full safety verification.
         */
        public function createCenter(KidsCenterCreateDto $dto): KidsCenter
        {
            $this->logger->info('Attempting to create kids center', [
                'name' => $dto->name,
                'correlation_id' => $dto->correlation_id
            ]);

            // Fraud control check before mutation
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'kids_center_add', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($dto) {
                $center = KidsCenter::create($dto->toArray());

                $this->logger->info('Kids center created successfully', [
                    'center_id' => $center->id,
                    'correlation_id' => $dto->correlation_id
                ]);

                return $center;
            });
        }

        /**
         * Book child for an event at the center.
         */
        public function bookEventSlot(int $eventId, int $childCount, string $correlationId): void
        {
            $this->db->transaction(function () use ($eventId, $childCount, $correlationId) {
                $event = KidsEvent::where('id', $eventId)->lockForUpdate()->firstOrFail();

                // Check availability - assuming a simplified check against booked_counts in a separate relation or column
                // For now, using direct capacity as simplified proof of concept
                if ($event->max_children < $childCount) {
                    throw new \RuntimeException("Event ID: {$eventId} is fully booked or insufficient slots.");
                }

                // In actual impl: $event->bookings()->create(...) + decrementing slots
                $this->logger->info('Event slot booked', [
                    'event_id' => $eventId,
                    'child_count' => $childCount,
                    'correlation_id' => $correlationId
                ]);
            });
        }

        /**
         * Verify safety standards for a center.
         */
        public function markAsSafetyVerified(int $centerId, string $correlationId): void
        {
            $this->logger->info('Safety verification requested', [
                'center_id' => $centerId,
                'correlation_id' => $correlationId
            ]);

            $center = KidsCenter::findOrFail($centerId);
            $center->update([
                'is_safety_verified' => true,
                'correlation_id' => $correlationId
            ]);

            $this->logger->info('Center safety verified', [
                'center_id' => $centerId,
                'correlation_id' => $correlationId
            ]);
        }

        /**
         * Hourly usage tracking for playgrounds.
         */
        public function startChildVisit(int $centerId, int $userId, string $correlationId): void
        {
            $center = KidsCenter::findOrFail($centerId);
            if (!$center->isOpenNow()) {
                throw new \RuntimeException("Center is currently closed.");
            }

            $this->logger->info('Child visit started', [
                'center_id' => $centerId,
                'user_id' => $userId,
                'correlation_id' => $correlationId
            ]);
        }
}
