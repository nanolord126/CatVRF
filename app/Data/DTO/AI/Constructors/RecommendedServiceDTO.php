<?php

declare(strict_types=1);

namespace App\Data\DTO\AI\Constructors;

use Spatie\LaravelData\Data;

final class RecommendedServiceDTO extends Data
{
    public function __construct(
        public readonly int $serviceId,
        public readonly int $masterId,
        public readonly string $serviceName,
        public readonly string $masterName,
        public readonly int $price,
        /** @var array<string> */
        public readonly array $availableSlots,
    ) {
    }
}
