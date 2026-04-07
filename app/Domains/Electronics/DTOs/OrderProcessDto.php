<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

/**
     * OrderProcessDto - For checkout operations.
     */
final readonly class OrderProcessDto
{
        /**
         * @param array<int, int> $items [productId => quantity]
         */
        public function __construct(
            public int $userId,
            public array $items,
            private string $mode = 'b2c', // b2c or b2b
            private ?string $businessId = null,
            private ?string $promoCode = null,
            private string $correlationId = '') {}
    }
