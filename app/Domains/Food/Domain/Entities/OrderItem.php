<?php

declare(strict_types=1);

namespace App\Domains\Food\Domain\Entities;

use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\Money;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Support\Collection;

final class OrderItem extends Entity
{
    /**
     * @param Uuid $id
     * @param Uuid $dishId
     * @param string $dishName
     * @param int $quantity
     * @param Money $unitPrice
     * @param Collection<Modifier> $modifiers
     * @param Uuid|null $correlationId
     */
    public function __construct(
        private readonly Uuid $id,
        private readonly Uuid $dishId,
        private readonly string $dishName,
        public int $quantity,
        private readonly Money $unitPrice,
        public Collection $modifiers,
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
            dishId: new Uuid($data['dish_id']),
            dishName: $data['dish_name'],
            quantity: $data['quantity'],
            unitPrice: new Money($data['unit_price']['amount'], $data['unit_price']['currency']),
            modifiers: collect($data['modifiers'] ?? [])->map(
                fn (array $modifier) => Modifier::fromArray($modifier)
            ),
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
            'dish_id' => $this->dishId->toString(),
            'dish_name' => $this->dishName,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice->toArray(),
            'modifiers' => $this->modifiers->map(fn (Modifier $modifier) => $modifier->toArray())->all(),
            'total_price' => $this->getTotalPrice()->toArray(),
            'correlation_id' => $this->correlationId?->toString(),
        ];
    }

    public function increaseQuantity(int $amount = 1): void
    {
        if ($amount <= 0) {
            // throw new \InvalidArgumentException('Amount must be positive.');
        }
        $this->quantity += $amount;
    }



    public function getTotalPrice(): Money
    {
        $modifiersPrice = $this->modifiers->reduce(function ($carry, Modifier $modifier) {
            return $carry + $modifier->priceAdjustment->getAmount();
        }, 0);

        $totalAmount = ($this->unitPrice->getAmount() + $modifiersPrice) * $this->quantity;

        return new Money($totalAmount, $this->unitPrice->getCurrency());
    }
}
