<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\DTOs;

use Spatie\LaravelData\Data;

final class OrderItemDTO extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $category,
        public readonly int $quantity,
        public readonly ?int $preparationMinutes,
        public readonly ?bool $isUrgent,
        public readonly ?array $modifiers,
    ) {}
}
