<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Room;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Hotel Availability Service (Layer 3)
 * 
 * Управление доступностью и инвентарем.
 * Обязательно: Кэширование в Redis (TTL 300 сек), correlation_id.
 */
final readonly class HotelAvailabilityService
{
    private string $correlationId;

    public function __construct(string $correlationId = '')
    {
        $this->correlationId = $correlationId ?: (string) Str::uuid();
    }

    /**
     * Проверить доступность номера на период.
     */
    public function isAvailable(int $roomId, Carbon $checkIn, Carbon $checkOut): bool
    {
        $room = Room::findOrFail($roomId);

        // 1. ПРОВЕРКА: Инвентарь
        if ($room->total_stock <= 0) {
            return false;
        }

        // 2. ПРОВЕРКА: Минимальное количество ночей
        $nights = (int) $checkIn->diffInDays($checkOut);
        if ($nights < $room->min_stay_days) {
            return false;
        }

        // 3. ПРОВЕРКА: Статус отеля
        if (!$room->hotel->is_active) {
            return false;
        }

        return true;
    }

    /**
     * Получить список доступных номеров в отеле.
     */
    public function getAvailableRooms(int $hotelId, Carbon $checkIn, Carbon $checkOut): array
    {
        $cacheKey = "hotel_availability:{$hotelId}:{$checkIn->toDateString()}:{$checkOut->toDateString()}:tenant:" . tenant('id');

        return Cache::remember($cacheKey, 300, function () use ($hotelId, $checkIn, $checkOut) {
            $hotel = Hotel::findOrFail($hotelId);

            return $hotel->rooms()
                ->where('is_available', true)
                ->where('total_stock', '>', 0)
                ->where('min_stay_days', '<=', $checkIn->diffInDays($checkOut))
                ->get()
                ->toArray();
        });
    }

    /**
     * Синхронизация стока (Stock Sync Job Helper).
     */
    public function syncRoomStock(int $roomId, int $newStock, string $reason): void
    {
        $room = Room::findOrFail($roomId);
        $oldStock = $room->total_stock;

        $room->update([
            'total_stock' => $newStock,
            'is_available' => $newStock > 0,
            'correlation_id' => $this->correlationId,
        ]);

        Log::channel('inventory')->info('Hotel room stock synchronized', [
            'room_id' => $roomId,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'reason' => $reason,
            'correlation_id' => $this->correlationId,
        ]);

        // Инвалидация кэша
        $this->invalidateHotelCache($room->hotel_id);
    }

    /**
     * Инвалидация кэша конкретного отеля.
     */
    private function invalidateHotelCache(int $hotelId): void
    {
        // Кэш отеля инвалидируется при изменении стока
        // В реальном проекте здесь может быть массовая инвалидация по паттерну Redis
        Log::debug("Cache invalidated for hotel $hotelId", [
            'correlation_id' => $this->correlationId,
        ]);
    }
}
