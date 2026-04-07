<?php

declare(strict_types=1);

namespace App\Domains\Food\Domain\Entities;

use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\Money;
use App\Shared\Domain\ValueObjects\Uuid;

final class Modifier extends Entity
{
    /**
     * @param Uuid $id
     * @param string $name
     * @param Money $priceAdjustment
     * @param bool $isAvailable
     * @param Uuid|null $correlationId
     */
    public function __construct(
        private readonly Uuid $id,
        public string $name,
        public Money $priceAdjustment,
        private bool $isAvailable = true,
        private ?Uuid $correlationId = null
    ) {
        parent::__construct($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: new Uuid($data['id']),
            name: $data['name'],
            priceAdjustment: new Money(
                $data['price_adjustment']['amount'],
                $data['price_adjustment']['currency']
            ),
            isAvailable: $data['is_available'] ?? true,
            correlationId: isset($data['correlation_id']) ? new Uuid($data['correlation_id']) : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'name' => $this->name,
            'price_adjustment' => $this->priceAdjustment->toArray(),
            'is_available' => $this->isAvailable,
            'correlation_id' => $this->correlationId?->toString(),
        ];
    }

    public function markAsAvailable(): void
    {
        $this->isAvailable = true;
    }

    public function markAsUnavailable(): void
    {
        $this->isAvailable = false;
    }

    public function hasPriceAdjustment(): bool
    {
        return !$this->priceAdjustment->isZero();
    }
}
