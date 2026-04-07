<?php

declare(strict_types=1);

namespace App\Domains\Gardening\DTOs;

/**
     * Subscription Box Update DTO
     */
final readonly class SubscriptionBoxDto
{
        public function __construct(
            public string $name,
            public string $frequency,
            public int $price,
            public array $contents,
            private bool $isActive = true,
            private ?string $correlationId = null
        ) {}
    }
