<?php

declare(strict_types=1);

namespace App\Domains\Food\Domain\Entities;

use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Support\Collection;

final class MenuSection extends Entity
{
    /**
     * @param Uuid $id
     * @param string $name
     * @param string $description
     * @param Collection<Dish> $dishes
     * @param int $displayOrder
     * @param Uuid|null $correlationId
     */
    public function __construct(
        private readonly Uuid $id,
        public string $name,
        public string $description,
        public Collection $dishes,
        private int $displayOrder = 0,
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
            description: $data['description'],
            dishes: collect($data['dishes'] ?? [])->map(
                fn (array $dish) => Dish::fromArray($dish)
            ),
            displayOrder: $data['display_order'] ?? 0,
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
            'dishes' => $this->dishes->map(fn (Dish $dish) => $dish->toArray())->all(),
            'display_order' => $this->displayOrder,
            'correlation_id' => $this->correlationId?->toString(),
        ];
    }

    public function addDish(Dish $dish): void
    {
        if ($this->dishes->contains('id', $dish->id)) {
            return;
        }
        $this->dishes->add($dish);
    }

    public function removeDish(Uuid $dishId): void
    {
        $this->dishes = $this->dishes->reject(fn (Dish $dish) => $dish->id->equals($dishId));
    }

    public function sortDishesByName(): void
    {
        $this->dishes = $this->dishes->sortBy('name')->values();
    }
}
