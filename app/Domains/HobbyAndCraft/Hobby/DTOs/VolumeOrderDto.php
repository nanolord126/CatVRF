<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\DTOs;

/**
     * VolumeOrderDto
     * Specific payload for institutional/B2B procurement of craft materials.
     */
final readonly class VolumeOrderDto
{
        public function __construct(
            public int $userId,
            public int $productId,
            public int $quantity,
            private bool $applyTaxExemption = false,
            private string $correlationId = ''
        ) {
            if ($this->quantity < 1) {
                throw new \InvalidArgumentException('Quantity must be at least 1.');
            }
        }
}
