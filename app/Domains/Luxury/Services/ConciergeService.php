<?php declare(strict_types=1);

namespace App\Domains\Luxury\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConciergeService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraudControl,
            private string $correlationId
        ) {}

        /**
         * Создать VIP-бронирование (товар или услуга)
         */
        public function createBooking(
            LuxuryClient $client,
            Model $bookable,
            array $data
        ): VIPBooking {
            // 1. Fraud Check
            $this->fraudControl->check([
                'user_id' => $client->user_id,
                'operation' => 'create_vip_booking',
                'amount' => $data['total_price_kopecks'] ?? 0,
                'correlation_id' => $this->correlationId
            ]);

            return DB::transaction(function () use ($client, $bookable, $data) {
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
                    'concierge_id' => $data['concierge_id'] ?? auth()->id(),
                    'notes' => $data['notes'] ?? null,
                    'correlation_id' => $this->correlationId,
                ]);

                // 4. Audit Log
                Log::channel('audit')->info('VIP Booking Created', [
                    'booking_uuid' => $booking->uuid,
                    'client_uuid' => $client->uuid,
                    'correlation_id' => $this->correlationId,
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
