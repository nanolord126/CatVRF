<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

/**
     * WarrantyRegisterDto - For post-sale service tracking.
     */
final readonly class WarrantyRegisterDto
{
        public function __construct(
            public int $productId,
            public string $serialNumber,
            public string $orderId,
            public int $userId,
            private int $monthsDuration = 12,
            private string $correlationId = '') {}
    }
