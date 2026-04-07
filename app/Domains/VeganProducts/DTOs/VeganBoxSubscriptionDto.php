<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\DTOs;

/**
     * VeganBoxSubscriptionDto - Data for setting up recurrent food deliveries.
     */
final readonly class VeganBoxSubscriptionDto
{
        public function __construct(
            public int $userId,
            public int $boxId,
            public string $planType, // weekly, monthly
            private array $exclusionAllergens = [],
            private ?string $promoCode = null,
            private ?string $correlationId = null) {}
    }
