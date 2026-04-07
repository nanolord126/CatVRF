<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\DTOs;

/**
     * VeganOrderProcessDto - Data for processing retail or B2B sales.
     */
final readonly class VeganOrderProcessDto
{
        public function __construct(
            public int $userId,
            public int $productId,
            public int $quantity,
            private bool $isB2B = false,
            private ?string $correlationId = null,
            private array $metadata = []) {}
    }
