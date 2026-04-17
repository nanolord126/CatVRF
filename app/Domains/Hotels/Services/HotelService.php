<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\HotelBooking;
use App\Domains\Hotels\Models\RoomType;
use App\Domains\Inventory\Services\InventoryManagementService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Основной сервис бронирования отелей.
 *
 * Layer 3: Services — CatVRF 2026.
 * Fraud-check, correlation_id, audit logging, DB::transaction обязательны.
 *
 * @package App\Domains\Hotels\Services
 */
final readonly class HotelService
{
    public function __construct(
        private FraudControlService $fraud,
        private InventoryManagementService $inventory,
        private PaymentServiceAdapter $payment,
        private WalletService $wallet,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private RateLimiter $rateLimiter,
    ) {}

    /**
     * Создание бронирования номера.
     *
     * @param int $userId ID пользователя
     * @param int $hotelId ID отеля
     * @param int $roomTypeId ID типа номера
     * @param Carbon $checkIn Дата заезда
     * @param Carbon $checkOut Дата выезда
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return HotelBooking Созданное бронирование
     */
    public function createBooking(
        int $userId,
        int $hotelId,
        int $roomTypeId,
        Carbon $checkIn,
        Carbon $checkOut,
        int $tenantId,
        string $correlationId = '',
    ): HotelBooking {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        // Rate Limiting — защита от ботов-бронировщиков
        $rateLimitKey = "hotel:booking:{$userId}";

        if ($this->rateLimiter->tooManyAttempts($rateLimitKey, 3)) {
            throw new \RuntimeException('Слишком много запросов на бронирование. Попробуйте позже.', 429);
        }

        $this->rateLimiter->hit($rateLimitKey, 3600);

        return $this->db->transaction(function () use ($userId, $hotelId, $roomTypeId, $checkIn, $checkOut, $tenantId, $correlationId): HotelBooking {
            $hotel = Hotel::findOrFail($hotelId);
            $roomType = RoomType::where('hotel_id', $hotelId)->findOrFail($roomTypeId);
            $nights = $checkIn->diffInDays($checkOut);

            if ($nights <= 0) {
                throw new \DomainException('Некорректный период проживания: дата выезда должна быть позже даты заезда.', 422);
            }

            $totalPriceKopecks = $roomType->price_per_night_kopecks * $nights;

            // Валидация доступности через Inventory
            $available = $this->inventory->getCurrentStock($roomType->id);

            if ($available <= 0) {
                throw new \DomainException('Нет свободных номеров данного типа.', 422);
            }

            // Fraud-check с полной сигнатурой
            $fraudResult = $this->fraud->check(
                userId: (int) ($this->guard->id() ?? 0),
                operationType: 'hotel_booking',
                amount: $totalPriceKopecks,
                ipAddress: null,
                deviceFingerprint: null,
                correlationId: $correlationId,
            );

            if (($fraudResult['decision'] ?? '') === 'block') {
                $this->logger->warning('Hotel: fraud block', [
                    'user_id' => $userId,
                    'score' => $fraudResult['score'] ?? 0,
                    'correlation_id' => $correlationId,
                ]);

                throw new \RuntimeException('Бронирование отклонено службой безопасности.', 403);
            }

            // Создание бронирования
            $booking = HotelBooking::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $hotel->tenant_id,
                'business_group_id' => $hotel->business_group_id,
                'user_id' => $userId,
                'hotel_id' => $hotelId,
                'room_type_id' => $roomTypeId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_price' => $totalPriceKopecks,
                'status' => 'pending',
                'correlation_id' => $correlationId,
                'tags' => ['vertical:hotels'],
            ]);

            // Холд номера в инвентаре (резерв на 20 минут по канону)
            $this->inventory->reserveStock(
                itemId: $roomType->id,
                quantity: 1,
                sourceType: 'hotel_booking',
                sourceId: $booking->id,
                correlationId: $correlationId,
            );

            $this->audit->log(
                action: 'hotel_booking_created',
                subjectType: HotelBooking::class,
                subjectId: $booking->id,
                old: [],
                new: $booking->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel: booking created', [
                'booking_id' => $booking->id,
                'hotel_id' => $hotelId,
                'total_price' => $totalPriceKopecks,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Подтверждение заезда (выплата отелю через 4 дня после выезда — КАНОН).
     */
    public function confirmCheckIn(int $bookingId, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $booking = HotelBooking::findOrFail($bookingId);

        $this->db->transaction(function () use ($booking, $correlationId): void {
            $booking->update(['status' => 'checked_in']);

            $this->audit->log(
                action: 'hotel_checkin_confirmed',
                subjectType: HotelBooking::class,
                subjectId: $booking->id,
                old: ['status' => 'pending'],
                new: ['status' => 'checked_in'],
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel: guest checked in', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Отмена бронирования.
     */
    public function cancelBooking(int $bookingId, string $reason, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $booking = HotelBooking::findOrFail($bookingId);

        $this->db->transaction(function () use ($booking, $reason, $correlationId): void {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => 'cancelled',
                'meta' => ['cancel_reason' => $reason],
            ]);

            // Снятие резерва из инвентаря
            $this->inventory->releaseStock(
                itemId: $booking->room_type_id,
                quantity: 1,
                sourceType: 'hotel_booking',
                sourceId: $booking->id,
                correlationId: $correlationId,
            );

            $this->audit->log(
                action: 'hotel_booking_cancelled',
                subjectType: HotelBooking::class,
                subjectId: $booking->id,
                old: ['status' => $oldStatus],
                new: ['status' => 'cancelled', 'reason' => $reason],
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel: booking cancelled', [
                'booking_id' => $booking->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
