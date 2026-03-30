<?php declare(strict_types=1);

namespace App\Domains\Travel\DTO;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public int $userId,
            public string $bookableType,
            public int $bookableId,
            public int $slotsCount,
            public string $correlationId,
            public ?string $idempotencyKey = null,
            public array $metadata = []
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
