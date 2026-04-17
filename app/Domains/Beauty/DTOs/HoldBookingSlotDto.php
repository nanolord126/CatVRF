<?php declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class HoldBookingSlotDto extends Data
{
    public function __construct(
        #[Required]
        public int $bookingSlotId,
        #[Required]
        public int $customerId,
        #[Required]
        public int $tenantId,
        public ?int $businessGroupId,
        public bool $isB2b,
        public string $correlationId,
        public ?string $idempotencyKey,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            bookingSlotId: $data['booking_slot_id'],
            customerId: $data['customer_id'],
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            isB2b: $data['is_b2b'] ?? false,
            correlationId: $data['correlation_id'] ?? \Illuminate\Support\Str::uuid()->toString(),
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }
}
