<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Application\DTO;

use Spatie\LaravelData\Data;
use Carbon\Carbon;

/**
 * Class BookingDTO
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final readonly class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Hotels\Application\DTO
 */
final readonly class BookingDTO extends Data
{
    public function __construct(
        private readonly ?string $id,
        private readonly string $hotel_id,
        private readonly string $room_id,
        private readonly int $user_id,
        private readonly Carbon $check_in_date,
        private readonly Carbon $check_out_date,
        private readonly int $total_price,
        private readonly string $status,
        private ?string $correlation_id = null) {
    }

    /**
     * Convert DTO to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'room_id' => $this->room_id,
            'user_id' => $this->user_id,
            'check_in_date' => $this->check_in_date->toIso8601String(),
            'check_out_date' => $this->check_out_date->toIso8601String(),
            'total_price' => $this->total_price,
            'status' => $this->status,
            'correlation_id' => $this->correlation_id,
        ];
    }
}
