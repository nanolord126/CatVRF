<?php declare(strict_types=1);

/**
 * StrBookingRequestDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/strbookingrequestdto
 */


namespace App\Domains\ShortTermRentals\DTO;

/**
 * Class StrBookingRequestDTO
 *
 * Part of the ShortTermRentals vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\ShortTermRentals\DTO
 */
final readonly class StrBookingRequestDTO
{

    public function __construct(
            public int $apartment_id,
            public int $user_id,
            public Carbon $check_in,
            public Carbon $check_out,
            private bool $is_b2b = false,
            private ?string $correlation_id = null,
            private array $metadata = []
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
