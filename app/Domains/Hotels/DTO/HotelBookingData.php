<?php declare(strict_types=1);

namespace App\Domains\Hotels\DTO;

use Carbon\Carbon;

/**
 * КАНОН 2026: Hotel Booking Request DTO (Layer 6)
 * 
 * Final Readonly DTO.
 */
final readonly class HotelBookingData
{
    public function __construct(
        public int $room_id,
        public Carbon $check_in,
        public Carbon $check_out,
        public bool $is_b2b = false,
        public ?int $contract_id = null,
        public array $metadata = []
    ) {}

    /**
     * Создать из запроса/массива.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            room_id: (int) $data['room_id'],
            check_in: Carbon::parse($data['check_in']),
            check_out: Carbon::parse($data['check_out']),
            is_b2b: (bool) ($data['is_b2b'] ?? false),
            contract_id: isset($data['contract_id']) ? (int) $data['contract_id'] : null,
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'room_id' => $this->room_id,
            'check_in' => $this->check_in->toDateString(),
            'check_out' => $this->check_out->toDateString(),
            'is_b2b' => $this->is_b2b,
            'contract_id' => $this->contract_id,
            'metadata' => $this->metadata,
        ];
    }
}
