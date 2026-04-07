<?php

declare(strict_types=1);

namespace App\Domains\Gardening\DTOs;

/**
     * Review Submission DTO
     */
final readonly class ReviewInputDto
{
        public function __construct(
            public int $productId,
            public int $userId,
            public int $rating,
            public string $comment,
            private ?array $growthHistory = null,
            private string $correlationId = ""
        ) {}
    }
