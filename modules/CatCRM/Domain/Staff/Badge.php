<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\BadgeId;

/**
 * Badge — Бейдж в системе геймификации
 * 
 * Readonly DDD entity для представления бейджа
 */
final readonly class Badge
{
    public function __construct(
        public BadgeId $id,
        public int $tenantId,
        public string $name,
        public string $description,
        public string $icon,
        public string $category, // performance, learning, social, wellness
        public int $pointsRequired,
        public string $condition, // Условие получения
        public bool $isRare,
        public bool $isLegendary,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}
}
