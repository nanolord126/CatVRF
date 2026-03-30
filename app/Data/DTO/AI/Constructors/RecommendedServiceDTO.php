<?php declare(strict_types=1);

namespace App\Data\DTO\AI\Constructors;

/** @phpstan-type AvailableSlots array<int, string> */
final readonly class RecommendedServiceDTO
{
    /** @param AvailableSlots $availableSlots */
    public function __construct(
        public int $serviceId,
        public int $masterId,
        public string $serviceName,
        public string $masterName,
        public int $price,
        public array $availableSlots,
    ) {
    }
}
