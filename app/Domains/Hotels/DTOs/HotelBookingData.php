<?php

declare(strict_types=1);

namespace App\Domains\Hotels\DTOs;

use Carbon\Carbon;

/**
 * DTO для данных бронирования отеля.
 * Layer 2: DTOs — CatVRF 2026
 *
 * Data Transfer Object (immutable).
 * Содержит основные параметры бронирования номера:
 * даты заезда/выезда, тип (B2C/B2B), контракт, метаданные.
 *
 * @package App\Domains\Hotels\DTOs
 */
final readonly class HotelBookingData
{
    public function __construct(
        private int $roomId,
        private Carbon $checkIn,
        private Carbon $checkOut,
        private int $tenantId,
        private string $correlationId,
        private bool $isB2B = false,
        private ?int $contractId = null,
        private ?int $businessGroupId = null,
        private array $metadata = [],
    ) {}

    /**
     * Создать из массива данных.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, int $tenantId, string $correlationId): self
    {
        return new self(
            roomId:          (int) $data['room_id'],
            checkIn:         Carbon::parse($data['check_in']),
            checkOut:        Carbon::parse($data['check_out']),
            tenantId:        $tenantId,
            correlationId:   $correlationId,
            isB2B:           (bool) ($data['is_b2b'] ?? false),
            contractId:      isset($data['contract_id']) ? (int) $data['contract_id'] : null,
            businessGroupId: isset($data['business_group_id']) ? (int) $data['business_group_id'] : null,
            metadata:        (array) ($data['metadata'] ?? []),
        );
    }

    /**
     * Преобразовать в массив для модели.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'room_id'           => $this->roomId,
            'check_in'          => $this->checkIn->toDateString(),
            'check_out'         => $this->checkOut->toDateString(),
            'tenant_id'         => $this->tenantId,
            'correlation_id'    => $this->correlationId,
            'is_b2b'            => $this->isB2B,
            'contract_id'       => $this->contractId,
            'business_group_id' => $this->businessGroupId,
            'metadata'          => $this->metadata,
        ];
    }

    public function getRoomId(): int
    {
        return $this->roomId;
    }

    public function getCheckIn(): Carbon
    {
        return $this->checkIn;
    }

    public function getCheckOut(): Carbon
    {
        return $this->checkOut;
    }

    /**
     * Количество ночей проживания.
     */
    public function getNights(): int
    {
        return (int) $this->checkIn->diffInDays($this->checkOut);
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function isB2B(): bool
    {
        return $this->isB2B;
    }

    public function getContractId(): ?int
    {
        return $this->contractId;
    }

    public function getBusinessGroupId(): ?int
    {
        return $this->businessGroupId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
