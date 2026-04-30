<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Entities;

final readonly class ProductIngredient
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isAllergen,
        public ?string $commonAllergyName,
        public ?string $description,
    ) {
    }
}
