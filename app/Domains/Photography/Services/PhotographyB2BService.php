<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PhotographyB2BService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Массовое бронирование для бизнеса (Корпоративные съемки)
         */
        public function createBatchCorporateBooking(
            int $tenantId,
            int $sessionId,
            array $timeSlots, // Array of datetimes
            ?string $correlationId = null
        ): array {
            $correlationId ??= (string) \Illuminate\Support\Str::uuid();
            $results = [];

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($tenantId, $sessionId, $timeSlots, $correlationId, &$results) {

                $this->logger->info('B2B Corporate Batch Session Booking Triggered', [
                    'tenant_id' => $tenantId,
                    'slots_count' => count($timeSlots),
                    'correlation_id' => $correlationId
                ]);

                foreach ($timeSlots as $slot) {
                    // Создание отдельных записей для каждого слота
                    $booking = Booking::create([
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'client_id' => 0, // System B2B marker
                        'session_id' => $sessionId,
                        'starts_at' => $slot['start'],
                        'ends_at' => $slot['end'],
                        'status' => 'confirmed',
                        'total_amount_kopecks' => 0, // B2B contract pricing
                        'correlation_id' => $correlationId,
                        'tags' => ['b2b', 'corporate', 'batch']
                    ]);
                    $results[] = $booking->uuid;
                }

                $this->logger->info('B2B Corporate Batch Complete', [
                    'count' => count($results),
                    'correlation_id' => $correlationId
                ]);

                return $results;
            });
        }
}
