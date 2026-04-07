<?php declare(strict_types=1);

namespace App\Domains\Fashion\DTO;

/**
 * Class FashionOrderDto
 *
 * Part of the Fashion vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Fashion\DTO
 */
final readonly class FashionOrderDto
{

    public function __construct(
            public int $tenant_id,
            public int $user_id,
            public int $store_id,
            public array $items,
            public int $total_amount,
            private string $currency = 'RUB',
            private ?string $inn = null, // Только для B2B
            private string $status = 'pending',
            private string $correlation_id = '') {
            if (empty($this->correlation_id)) {
                throw new \InvalidArgumentException('FashionOrderDto: correlation_id is mandatory');
            }
        }

        /**
         * Создать из массива данных (Request)
         */
        public static function fromArray(array $data): self
        {
            return new self(
                tenant_id: (int)($data['tenant_id'] ?? 0),
                user_id: (int)($data['user_id'] ?? 0),
                store_id: (int)($data['store_id'] ?? 0),
                items: (array)($data['items'] ?? []),
                total_amount: (int)($data['total_amount'] ?? 0),
                currency: (string)($data['currency'] ?? 'RUB'),
                inn: $data['inn'] ?? null,
                status: (string)($data['status'] ?? 'pending'),
                correlation_id: (string)($data['correlation_id'] ?? \Illuminate\Support\Str::uuid()->toString()),
            );
        }

        public function toArray(): array
        {
            return [
                'tenant_id' => $this->tenant_id,
                'user_id' => $this->user_id,
                'store_id' => $this->store_id,
                'items' => $this->items,
                'total_amount' => $this->total_amount,
                'currency' => $this->currency,
                'inn' => $this->inn,
                'status' => $this->status,
                'correlation_id' => $this->correlation_id,
            ];
        }
}
