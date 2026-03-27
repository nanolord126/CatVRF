<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\DTO;

use Carbon\Carbon;

/**
 * КАНОН 2026: DTO для запроса на бронирование апартаментов
 */
final readonly class StrBookingRequestDTO
{
    public function __construct(
        public int $apartment_id,
        public int $user_id,
        public Carbon $check_in,
        public Carbon $check_out,
        public bool $is_b2b = false,
        public ?string $correlation_id = null,
        public array $metadata = []
    ) {}

    /**
     * Создание DTO из массива данных (Request)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            apartment_id: (int) $data['apartment_id'],
            user_id: (int) $data['user_id'],
            check_in: Carbon::parse($data['check_in']),
            check_out: Carbon::parse($data['check_out']),
            is_b2b: (bool) ($data['is_b2b'] ?? false),
            correlation_id: $data['correlation_id'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }
}
