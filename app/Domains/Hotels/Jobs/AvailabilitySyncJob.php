<?php declare(strict_types=1);

namespace App\Domains\Hotels\Jobs;

use App\Domains\Hotels\Models\Room;
use App\Domains\Hotels\Services\HotelAvailabilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026: Availability Sync Job (Layer 5)
 * 
 * Синхронизация остатков номеров с внешними PMS (периодическая).
 */
final class AvailabilitySyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $hotelId,
        public readonly array $syncData, // JSON со стоком из внешней системы
        public readonly string $correlationId
    ) {}

    public function handle(HotelAvailabilityService $availabilityService): void
    {
        Log::channel('inventory')->info('Hotel Availability Sync Started', [
            'hotel_id' => $this->hotelId,
            'correlation_id' => $this->correlationId,
        ]);

        foreach ($this->syncData as $roomSync) {
            $roomId = $roomSync['room_id'];
            $newStock = (int) $roomSync['stock'];
            $reason = "External Sync: " . ($roomSync['source'] ?? 'Unknown');

            $availabilityService->syncRoomStock($roomId, $newStock, $reason);
        }

        Log::channel('inventory')->info('Hotel Availability Sync Completed', [
            'hotel_id' => $this->hotelId,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function tags(): array
    {
        return ['hotel', 'inventory', 'sync', 'hotel_id:' . $this->hotelId];
    }
}
