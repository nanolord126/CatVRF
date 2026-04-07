<?php declare(strict_types=1);

namespace App\Domains\Travel\DTO;

/**
 * Class BookingDto
 *
 * Part of the Travel vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Travel\DTO
 */
final readonly class BookingDto
{

    public function __construct(
            public int $userId,
            public string $bookableType,
            public int $bookableId,
            public int $slotsCount,
            public string $correlationId,
            private ?string $idempotencyKey = null,
            private array $metadata = []
        ) {}

        public static function fromRequest(array $data, int $userId): self
        {
            return new self(
                userId: $userId,
                bookableType: (string) ($data['bookable_type'] ?? 'trip'),
                bookableId: (int) ($data['bookable_id'] ?? 0),
                slotsCount: (int) ($data['slots_count'] ?? 1),
                correlationId: (string) ($data['correlation_id'] ?? \Illuminate\Support\Str::uuid()),
                idempotencyKey: $data['idempotency_key'] ?? null,
                metadata: $data['metadata'] ?? []
            );
        }

        /**
         * Handle toArray operation.
         *
         * @throws \DomainException
         */
        public function toArray(): array
        {
            return [
                'user_id' => $this->userId,
                'bookable_type' => $this->bookableType,
                'bookable_id' => $this->bookableId,
                'slots_count' => $this->slotsCount,
                'correlation_id' => $this->correlationId,
                'idempotency_key' => $this->idempotencyKey,
                'metadata' => $this->metadata,
            ];
        }
}
