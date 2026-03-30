<?php declare(strict_types=1);

namespace App\Domains\Hotels\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AvailabilitySyncJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
