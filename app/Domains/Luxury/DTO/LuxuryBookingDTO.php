<?php

declare(strict_types=1);

namespace App\Domains\Luxury\DTO;

/**
 * LuxuryBookingDTO
 *
 * Layer 5: Data Transfer Object
 * Описывает структуру данных для создания бронирования.
 * Иммутабельность и типизация.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final readonly class LuxuryBookingDTO
{
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
