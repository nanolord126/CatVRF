<?php

declare(strict_types=1);

namespace App\Domains\Food\Application\B2C\DataTransferObjects;

use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Support\Collection;

/**
 * Class CartItemDto
 *
 * Part of the Food vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Food\Application\B2C\DataTransferObjects
 */
final readonly class CartItemDto
{
    /**
     * @param Uuid $dishId
     * @param int $quantity
     * @param Collection<Uuid> $modifierIds
     */
    public function __construct(
        public Uuid $dishId,
        public int $quantity,
        public Collection $modifierIds
    ) {

    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dishId: new Uuid($data['dish_id']),
            quantity: $data['quantity'],
            modifierIds: collect($data['modifier_ids'] ?? [])->map(fn (string $id) => new Uuid($id))
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dish_id' => $this->dishId->toString(),
            'quantity' => $this->quantity,
            'modifier_ids' => $this->modifierIds->map(fn (Uuid $id) => $id->toString())->all(),
        ];
    }
}
