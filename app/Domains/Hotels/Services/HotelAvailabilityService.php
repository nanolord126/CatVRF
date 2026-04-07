<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Room;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Сервис проверки доступности номеров.
 * Layer 3: Services — CatVRF 2026
 *
 * Проверяет наличие номеров, кэширует результаты,
 * синхронизирует сток и инвалидирует кэш.
 *
 * @package App\Domains\Hotels\Services
 */
final readonly class HotelAvailabilityService
{
    public function __construct(
        private CacheRepository $cache,
        private DatabaseManager $db,
        private FraudControlService $fraud,
        private AuditService $audit,
        private LoggerInterface $logger,
    ) {}

    /**
     * Проверить доступность конкретного номера на период.
     */
    public function isAvailable(int $roomId, Carbon $checkIn, Carbon $checkOut, string $correlationId): bool
    {
        $room = Room::findOrFail($roomId);

        if ($room->total_stock <= 0) {
            $this->logger->debug('Room unavailable: zero stock', [
                'room_id'        => $roomId,
                'correlation_id' => $correlationId,
            ]);

            return false;
        }

        $nights = (int) $checkIn->diffInDays($checkOut);

        if ($nights < ($room->min_stay_days ?? 1)) {
            $this->logger->debug('Room unavailable: minimum stay not met', [
                'room_id'        => $roomId,
                'min_stay_days'  => $room->min_stay_days,
                'requested'      => $nights,
                'correlation_id' => $correlationId,
            ]);

            return false;
        }

        if (!$room->hotel->is_active) {
            $this->logger->debug('Room unavailable: hotel inactive', [
                'room_id'        => $roomId,
                'hotel_id'       => $room->hotel_id,
                'correlation_id' => $correlationId,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Получить список доступных номеров в отеле на заданный период.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableRooms(int $hotelId, Carbon $checkIn, Carbon $checkOut, int $tenantId, string $correlationId): array
    {
        $cacheKey = "hotel_availability:{$hotelId}:{$checkIn->toDateString()}:{$checkOut->toDateString()}:tenant:{$tenantId}";

        return $this->cache->remember($cacheKey, 300, function () use ($hotelId, $checkIn, $checkOut, $correlationId) {
            $hotel = Hotel::findOrFail($hotelId);
            $minNights = (int) $checkIn->diffInDays($checkOut);

            $rooms = $hotel->rooms()
                ->where('is_available', true)
                ->where('total_stock', '>', 0)
                ->where('min_stay_days', '<=', $minNights)
                ->get();

            $this->logger->info('Available rooms fetched', [
                'hotel_id'       => $hotelId,
                'rooms_count'    => $rooms->count(),
                'correlation_id' => $correlationId,
            ]);

            return $rooms->toArray();
        });
    }

    /**
     * Синхронизация стока номера (внешний источник / инвентаризация).
     */
    public function syncRoomStock(int $roomId, int $newStock, string $reason, int $userId, string $correlationId): void
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_stock_sync',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $this->db->transaction(function () use ($roomId, $newStock, $reason, $correlationId) {
            $room = Room::lockForUpdate()->findOrFail($roomId);
            $oldStock = $room->total_stock;

            $room->update([
                'total_stock'    => $newStock,
                'is_available'   => $newStock > 0,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                action: 'hotel_room_stock_synced',
                subjectType: Room::class,
                subjectId: $roomId,
                old: ['total_stock' => $oldStock],
                new: ['total_stock' => $newStock],
                correlationId: $correlationId,
            );

            $this->logger->info('Hotel room stock synchronized', [
                'room_id'        => $roomId,
                'old_stock'      => $oldStock,
                'new_stock'      => $newStock,
                'reason'         => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->invalidateHotelCache($room->hotel_id, $correlationId);
        });
    }

    /**
     * Инвалидация кэша отеля при изменении стока.
     */
    private function invalidateHotelCache(int $hotelId, string $correlationId): void
    {
        $this->cache->forget("hotel_availability:{$hotelId}:*");

        $this->logger->debug('Hotel availability cache invalidated', [
            'hotel_id'       => $hotelId,
            'correlation_id' => $correlationId,
        ]);
    }
}
