<?php

declare(strict_types=1);

namespace App\Domains\SportsNutrition\DTOs;

/**
     * SubscriptionBoxDto (Layer 2/9)
     */
final readonly class SubscriptionBoxDto
{
        public function __construct(
            public string $name,
            public string $description,
            public int $price_monthly,
            public array $included_skus,
            public string $training_goal,
            private bool $is_active = true
        ) {}
}
