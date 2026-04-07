<?php

declare(strict_types=1);

namespace App\Domains\Furniture\DTOs;

/**
     * DTO for processing a custom AI-driven interior order.
     */
final readonly class FurnitureCustomOrderDto
{
        public function __construct(
            public int $userId,
            public int $roomTypeId,
            public int $totalAmount,
            public array $aiSpecification,
            private array $photoAnalysis = [],
            private bool $includeAssembly = true,
            private ?string $correlationId = null
        ) {}
    }
