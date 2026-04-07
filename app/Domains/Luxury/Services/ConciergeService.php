<?php declare(strict_types=1);

namespace App\Domains\Luxury\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ConciergeService
{

    public function __construct(private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создать VIP-бронирование (товар или услуга)
         */
        public function createBooking(
            LuxuryClient $client,
            Model $bookable,
            array $data
        ): VIPBooking {
            // 1. Fraud Check
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_vip_booking', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($client, $bookable, $data) {
                // 2. Логика холда стока (если это товар)
                if ($bookable instanceof LuxuryProduct) {
                    if ($bookable->current_stock <= 0) {
                        throw LuxuryServiceException::outOfStock($bookable->name);
                    }
                    $bookable->increment('hold_stock');
                }

                // 3. Создание бронирования
                $booking = VIPBooking::create([
                    'tenant_id' => $client->tenant_id,
                    'client_id' => $client->id,
                    'bookable_type' => get_class($bookable),
                    'bookable_id' => $bookable->id,
                    'status' => 'pending',
                    'booking_at' => $data['booking_at'],
                    'duration_minutes' => $data['duration_minutes'] ?? 0,
                    'total_price_kopecks' => $data['total_price_kopecks'] ?? 0,
                    'deposit_kopecks' => $data['deposit_kopecks'] ?? 0,
                    'payment_status' => 'unpaid',
                    'concierge_id' => $data['concierge_id'] ?? $this->guard->id(),
                    'notes' => $data['notes'] ?? null,
                    'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                ]);

                // 4. Audit Log
                $this->logger->info('VIP Booking Created', [
                    'booking_uuid' => $booking->uuid,
                    'client_uuid' => $client->uuid,
                    'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
                ]);

                return $booking;
            });
        }

        /**
         * Получить список доступных эксклюзивных предложений для клиента
         */
        public function getEligibleOffers(LuxuryClient $client): Collection
        {
            return \App\Domains\Luxury\Models\LuxuryOffer::where('is_public', true)
                ->orWhereJsonContains('target_vip_levels', $client->vip_level)
                ->where('valid_from', '<=', now())
                ->where('valid_until', '>=', now())
                ->get();
        }
}
