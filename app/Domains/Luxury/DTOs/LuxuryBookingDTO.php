<?php declare(strict_types=1);

namespace App\Domains\Luxury\DTO;

/**
 * Class LuxuryBookingDTO
 *
 * Part of the Luxury vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final readonly class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Luxury\DTO
 */
final readonly class LuxuryBookingDTO
{

    public function __construct(
            public string $clientUuid,
            public string $bookableType,
            public string $bookableUuid,
            public string $bookingAt,
            private ?int $durationMinutes = null,
            private readonly ?int $totalPriceKopecks = null,
            private readonly ?int $depositKopecks = null,
            private readonly ?string $notes = null,
            private readonly ?int $conciergeId = null,
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
