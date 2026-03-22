<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\HotelBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class HotelService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        if (!$this->correlationId) {
            $this->correlationId = Str::uuid()->toString();
        }
    }

    /**
     * Создать бронирование
     */
    public function createBooking(
        int $hotelId,
        int $userId,
        int $roomTypeId,
        \DateTime $checkInDate,
        \DateTime $checkOutDate,
        int $numberOfGuests,
        string $guestName,
        string $guestEmail,
        string $guestPhone,
        string $specialRequests = '',
    ): HotelBooking {


        return DB::transaction(function () use (
            $hotelId,
            $userId,
            $roomTypeId,
            $checkInDate,
            $checkOutDate,
            $numberOfGuests,
            $guestName,
            $guestEmail,
            $guestPhone,
            $specialRequests,
        ) {
            // Проверка отеля
            $hotel = Hotel::lockForUpdate()->findOrFail($hotelId);
            if (!$hotel->isOpen()) {
                throw new \Exception('Отель закрыт', 400);
            }

            // Проверка фрода
            $this->fraudControlService->check([
                'user_id' => $userId,
                'operation_type' => 'hotel_booking',
                'amount' => 0,
                'correlation_id' => $this->correlationId,
            ]);

            // Расчёт кол-ва ночей и стоимости
            $nights = $checkOutDate->diffInDays($checkInDate);
            $roomType = $hotel->roomTypes()->findOrFail($roomTypeId);
            $totalCost = $roomType->price_per_night * $nights;
            $depositAmount = (int) ($totalCost * 0.2); // 20% депозит

            // Создание бронирования
            $booking = HotelBooking::create([
                'hotel_id' => $hotelId,
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
                'booking_number' => 'BK-' . strtoupper(Str::random(10)),
                'room_type_id' => $roomTypeId,
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'guest_phone' => $guestPhone,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'number_of_guests' => $numberOfGuests,
                'number_of_nights' => $nights,
                'room_price_per_night' => $roomType->price_per_night,
                'total_nights_cost' => $totalCost,
                'deposit_amount' => $depositAmount,
                'total_cost' => $totalCost,
                'status' => 'pending',
                'payment_status' => 'pending',
                'special_requests' => $specialRequests,
            ]);

            // Логирование
            Log::channel('audit')->info('Бронирование отеля создано', [
                'booking_id' => $booking->id,
                'hotel_id' => $hotelId,
                'user_id' => $userId,
                'nights' => $nights,
                'cost' => $totalCost,
                'correlation_id' => $this->correlationId,
            ]);

            Cache::forget("hotel:bookings:{$hotelId}");

            return $booking;
        });
    }

    /**
     * Подтвердить бронирование и списать депозит
     */
    public function confirmBooking(HotelBooking $booking, int $userId): bool
    {


        return DB::transaction(function () use ($booking, $userId) {
            if ($booking->payment_status !== 'pending') {
                throw new \Exception('Бронирование уже подтверждено', 400);
            }

            // Списание депозита
            $this->walletService->debit(
                userId: $userId,
                amount: $booking->deposit_amount,
                reason: "Депозит за бронирование #{$booking->booking_number}",
                correlationId: $booking->correlation_id,
            );

            $booking->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);

            Log::channel('audit')->info('Бронирование подтверждено', [
                'booking_id' => $booking->id,
                'correlation_id' => $booking->correlation_id,
                'deposit' => $booking->deposit_amount,
            ]);

            return true;
        });
    }

    /**
     * Завершить бронирование (после выселения)
     */
    public function completeBooking(HotelBooking $booking): bool
    {


        $booking->update(['status' => 'completed']);

        Log::channel('audit')->info('Бронирование завершено', [
            'booking_id' => $booking->id,
            'correlation_id' => $booking->correlation_id,
        ]);

        return true;
    }

    /**
     * Отменить бронирование и вернуть депозит
     */
    public function cancelBooking(HotelBooking $booking, string $reason = ''): bool
    {


        return DB::transaction(function () use ($booking, $reason) {
            if (in_array($booking->status, ['completed', 'cancelled'])) {
                throw new \Exception('Невозможно отменить бронирование в текущем статусе', 400);
            }

            if ($booking->payment_status === 'paid') {
                // Возврат депозита
                $this->walletService->credit(
                    userId: $booking->user_id,
                    amount: $booking->deposit_amount,
                    reason: "Возврат депозита за отменённое бронирование #{$booking->booking_number}: {$reason}",
                    correlationId: $booking->correlation_id,
                );
            }

            $booking->update([
                'status' => 'cancelled',
                'meta->cancel_reason' => $reason,
            ]);

            Log::channel('audit')->info('Бронирование отменено', [
                'booking_id' => $booking->id,
                'correlation_id' => $booking->correlation_id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Произвести выплату владельцу отеля (через 4 дня после выселения)
     */
    public function payoutHotelOwner(HotelBooking $booking, int $hotelOwnerId): bool
    {


        return DB::transaction(function () use ($booking, $hotelOwnerId) {
            if ($booking->status !== 'completed') {
                throw new \Exception('Бронирование не завершено', 400);
            }

            // Списание комиссии платформы (14%)
            $platformFee = (int) ($booking->total_cost * 0.14);
            $ownerPayout = $booking->total_cost - $platformFee;

            // Зачисление на счёт владельца
            $this->walletService->credit(
                userId: $hotelOwnerId,
                amount: $ownerPayout,
                reason: "Выплата за бронирование #{$booking->booking_number} (минус комиссия {$platformFee})",
                correlationId: $booking->correlation_id,
            );

            Log::channel('audit')->info('Выплата владельцу отеля', [
                'booking_id' => $booking->id,
                'owner_id' => $hotelOwnerId,
                'total' => $booking->total_cost,
                'fee' => $platformFee,
                'payout' => $ownerPayout,
                'correlation_id' => $booking->correlation_id,
            ]);

            return true;
        });
    }

    /**
     * Получить активные отели
     */
    public function getActiveHotels(int $tenantId, int $limit = 50): Collection
    {


        return Hotel::where('tenant_id', $tenantId)
            ->where('is_open', true)
            ->orderByDesc('rating')
            ->limit($limit)
            ->get();
    }
}
