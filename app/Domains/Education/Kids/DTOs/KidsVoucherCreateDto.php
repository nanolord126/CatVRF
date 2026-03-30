<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsVoucherCreateDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param array<string, mixed> $metadata
         */
        public function __construct(
            public int $store_id,
            public int $customer_id,
            public string $voucher_type, // b2c_gift, b2b_credit, loyalty_reward
            public int $face_value, // in kopecks
            public \DateTimeInterface $expires_at,
            public bool $is_rechargeable = false,
            public array $metadata = [], // greeting, sender_name, recipient_name
            public ?string $correlation_id = null,
        ) {}

        /**
         * Create from request.
         */
        public static function fromRequest(array $data): self
        {
            return new self(
                store_id: (int) $data['store_id'],
                customer_id: (int) $data['customer_id'],
                voucher_type: $data['voucher_type'] ?? 'b2c_gift',
                face_value: (int) ($data['face_value'] ?? 0),
                expires_at: new \DateTime($data['expires_at'] ?? '+1 year'),
                is_rechargeable: (bool) ($data['is_rechargeable'] ?? false),
                metadata: $data['metadata'] ?? [],
                correlation_id: $data['correlation_id'] ?? null,
            );
        }

        /**
         * Convert to array for database.
         */
        public function toArray(): array
        {
            return [
                'store_id' => $this->store_id,
                'customer_id' => $this->customer_id,
                'voucher_type' => $this->voucher_type,
                'face_value' => $this->face_value,
                'current_balance' => $this->face_value, // Standard start value
                'expires_at' => $this->expires_at->format('Y-m-d H:i:s'),
                'is_rechargeable' => $this->is_rechargeable,
                'metadata' => $this->metadata,
                'correlation_id' => $this->correlation_id,
                'status' => 'active',
            ];
        }
}
