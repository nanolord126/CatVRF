<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Models\Room;
use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\B2BContract;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * КАНОН 2026: Hotel Booking Service (Layer 3)
 * 
 * Управление бронированиями, ценами и платежами.
 * Обязательно: DB::transaction(), correlation_id, audit-лог, fraud-check.
 */
final readonly class HotelBookingService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly WalletService $wallet,
        private readonly PaymentService $payment,
        private string $correlationId = '',
    ) {
        $this->correlationId = $this->correlationId ?: (string) Str::uuid();
    }

    /**
     * Создать бронирование номера.
     * 
     * @throws \Exception
     */
    public function initiateBooking(array $data): Booking
    {
        Log::channel('audit')->info('Hotel booking initiated', [
            'correlation_id' => $this->correlationId,
            'data' => $data,
        ]);

        // 1. ПЕРВАЯ ПРОВЕРКА: FraudControl
        $this->fraudControl->checkOperation('hotel_booking_init', $data);

        return DB::transaction(function () use ($data) {
            $room = Room::findOrFail($data['room_id']);
            $hotel = $room->hotel;

            // 2. ВТОРАЯ ПРОВЕРКА: Наличие
            if (!$room->is_available || $room->total_stock <= 0) {
                throw new \Exception('Room is not available or out of stock');
            }

            // 3. ТРЕТЬЯ ПРОВЕРКА: Минимальное количество ночей
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            $nights = (int) $checkIn->diffInDays($checkOut);

            if ($nights < $room->min_stay_days) {
                throw new \Exception("Minimum stay for this room is {$room->min_stay_days} nights");
            }

            // 4. ЦЕНООБРАЗОВАНИЕ (B2B/B2C)
            $totalPrice = $this->calculateTotalPrice($room, $nights, $data['is_b2b'] ?? false, $data['contract_id'] ?? null);

            // 5. СОЗДАНИЕ БРОНИРОВАНИЯ
            $booking = Booking::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => (int) tenant('id'),
                'business_group_id' => $hotel->business_group_id,
                'hotel_id' => $hotel->id,
                'room_id' => $room->id,
                'user_id' => (int) auth()->id(),
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'currency' => 'RUB',
                'payment_status' => 'pending',
                'is_b2b' => (bool) ($data['is_b2b'] ?? false),
                'contract_id' => $data['contract_id'] ?? null,
                'correlation_id' => $this->correlationId,
                'metadata' => [
                    'nights' => $nights,
                    'price_per_night' => $totalPrice / $nights,
                ],
            ]);

            // 6. ХОЛД В ИНВЕНТАРЕ
            $room->decrement('total_stock');

            Log::channel('audit')->info('Hotel booking created', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Подтвердить бронирование (после оплаты).
     */
    public function confirmBooking(int $bookingId, string $paymentId): void
    {
        DB::transaction(function () use ($bookingId, $paymentId) {
            $booking = Booking::findOrFail($bookingId);

            if ($booking->status !== 'pending') {
                throw new \Exception('Booking is already processed');
            }

            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'metadata' => array_merge($booking->metadata ?? [], [
                    'payment_id' => $paymentId,
                    'confirmed_at' => now()->toIso8601String(),
                ]),
                'payout_at' => now()->addDays(4), // КАНОН: 4 дня после выселения (payout_at в будущем)
            ]);

            Log::channel('audit')->info('Hotel booking confirmed', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }

    /**
     * Рассчитать полную стоимость.
     */
    private function calculateTotalPrice(Room $room, int $nights, bool $isB2B, ?int $contractId): int
    {
        $price = $isB2B ? $room->base_price_b2b : $room->base_price_b2c;

        if ($isB2B && $contractId) {
            $contract = B2BContract::find($contractId);
            if ($contract && $contract->isValid()) {
                $price = (int) ($price * (1 - $contract->discount_percent / 100));
            }
        }

        return $price * $nights;
    }

    /**
     * Извлечение correlation_id
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
