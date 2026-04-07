<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

/**
     * SubscriptionBoxUpdateDto (Layer 2/9)
     */
final readonly class SubscriptionBoxUpdateDto implements BooksDtoInterface
{
        public function __construct(
            public int $boxId,
            public string $name,
            public int $priceMonthly,
            private array $genreFocus = [],
            private ?string $correlationId = null
        ) {}
    }
