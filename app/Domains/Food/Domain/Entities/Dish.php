<?php

declare(strict_types=1);

namespace App\Domains\Food\Domain\Entities;

use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\Money;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Support\Collection;

final class Dish extends Entity
{
    /**
     * @param Uuid $id
     * @param string $name
     * @param string $description
     * @param Money $price
     * @param Collection<Modifier> $modifiers
     * @param int $cookingTimeMinutes
     * @param int|null $calories
     * @param bool $isAvailable
     * @param Collection<string> $allergens
     * @param Uuid|null $correlationId
     */
    public function __construct(
        private readonly Uuid $id,
        public string $name,
        public string $description,
        public Money $price,
        public Collection $modifiers,
        public int $cookingTimeMinutes,
        private ?int $calories = null,
        private bool $isAvailable = true,
        public Collection $allergens,
        private readonly ?Uuid $correlationId = null
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
            description: $data['description'],
            price: new Money($data['price']['amount'], $data['price']['currency']),
            modifiers: collect($data['modifiers'] ?? [])->map(
                fn (array $modifier) => Modifier::fromArray($modifier)
            ),
            cookingTimeMinutes: $data['cooking_time_minutes'],
            calories: $data['calories'] ?? null,
            isAvailable: $data['is_available'] ?? true,
            allergens: collect($data['allergens'] ?? []),
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
            'description' => $this->description,
            'price' => $this->price->toArray(),
            'modifiers' => $this->modifiers->map(fn (Modifier $modifier) => $modifier->toArray())->all(),
            'cooking_time_minutes' => $this->cookingTimeMinutes,
            'calories' => $this->calories,
            'is_available' => $this->isAvailable,
            'allergens' => $this->allergens->all(),
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

    public function addModifier(Modifier $modifier): void
    {
        if ($this->modifiers->contains('id', $modifier->id)) {
            // Or throw an exception
            return;
        }
        $this->modifiers->add($modifier);
    }

    public function removeModifier(Uuid $modifierId): void
    {
        $this->modifiers = $this->modifiers->reject(fn (Modifier $modifier) => $modifier->id->equals($modifierId));
    }
}
