<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\DTOs;

/**
     * VolumeToyOrderDto (Layer 2)
     * Normalized structure for bulk B2B B2B institutional procurement.
     */
final readonly class VolumeToyOrderDto
{
        public function __construct(
            public int $companyId,
            public int $storeId,
            public array $items, // array of ['toy_id' => int, 'quantity' => int]
            public string $correlationId,
            private bool $giftPackaging = false,
            private array $metadata = []
        ) {}
}
