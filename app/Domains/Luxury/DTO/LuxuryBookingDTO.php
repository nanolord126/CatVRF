<?php declare(strict_types=1);

namespace App\Domains\Luxury\DTO;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LuxuryBookingDTO extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public string $clientUuid,
            public string $bookableType,
            public string $bookableUuid,
            public string $bookingAt,
            public ?int $durationMinutes = null,
            public ?int $totalPriceKopecks = null,
            public ?int $depositKopecks = null,
            public ?string $notes = null,
            public ?int $conciergeId = null,
            public string $correlationId
        ) {}

        /**
         * Создать DTO из валидированного массива запроса
         */
        public static function fromRequest(array $data, string $correlationId): self
        {
            return new self(
                clientUuid: $data['client_uuid'],
                bookableType: $data['bookable_type'],
                bookableUuid: $data['bookable_uuid'],
                bookingAt: $data['booking_at'],
                durationMinutes: $data['duration_minutes'] ?? null,
                totalPriceKopecks: $data['total_price_kopecks'] ?? null,
                depositKopecks: $data['deposit_kopecks'] ?? null,
                notes: $data['notes'] ?? null,
                conciergeId: $data['concierge_id'] ?? null,
                correlationId: $correlationId
            );
        }

        /**
         * Преобразовать в массив для Eloquent
         */
        public function toArray(): array
        {
            return [
                'booking_at' => $this->bookingAt,
                'duration_minutes' => $this->durationMinutes,
                'total_price_kopecks' => $this->totalPriceKopecks,
                'deposit_kopecks' => $this->depositKopecks,
                'notes' => $this->notes,
                'concierge_id' => $this->conciergeId,
                'correlation_id' => $this->correlationId,
            ];
        }
}
