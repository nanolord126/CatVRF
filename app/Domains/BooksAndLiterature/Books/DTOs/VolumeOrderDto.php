<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

/**
     * VolumeOrderDto (Layer 2/9)
     * Specialized DTO for B2B/Corporate orders (Layer 2/9).
     */
final readonly class VolumeOrderDto implements BooksDtoInterface
{
        public function __construct(
            public int $userId,
            public int $tenantId,
            public string $type, // 'institutional', 'b2b'
            public array $items, // [{book_id, qty}]
            public string $shippingAddress,
            private bool $requestInvoice = true,
            private ?string $correlationId = null
        ) {}
    }
