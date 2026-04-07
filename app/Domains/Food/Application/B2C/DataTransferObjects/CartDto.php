<?php

declare(strict_types=1);

namespace App\Domains\Food\Application\B2C\DataTransferObjects;

use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Support\Collection;

/**
 * Class CartDto
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
final readonly class CartDto
{
    /**
     * @param Uuid $id
     * @param Uuid $clientId
     * @param Uuid $restaurantId
     * @param Collection<CartItemDto> $items
     */
    public function __construct(
        public Uuid $id,
        public Uuid $clientId,
        public Uuid $restaurantId,
        public Collection $items
    ) {

    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: new Uuid($data['id']),
            clientId: new Uuid($data['client_id']),
            restaurantId: new Uuid($data['restaurant_id']),
            items: collect($data['items'])->map(
                fn (array $item) => CartItemDto::fromArray($item)
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'client_id' => $this->clientId->toString(),
            'restaurant_id' => $this->restaurantId->toString(),
            'items' => $this->items->map(fn (CartItemDto $item) => $item->toArray())->all(),
        ];
    }
}
