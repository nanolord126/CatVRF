<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\DTOs;

/**
     * JewelryCustomOrderDto (Layer 2/9)
     */
final readonly class JewelryCustomOrderDto
{
        public function __construct(
            public int $storeId,
            public int $userId,
            public string $customerName,
            public string $customerPhone,
            public int $estimatedPrice,
            public array $aiSpecification,
            private ?string $userNotes = null,
            private ?string $referencePhotoPath = null,
            private ?string $correlationId = null
        ) {}
    }
