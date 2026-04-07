<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\Shared\DTOs;

use App\Domains\Auto\Taxi\Domain\Enums\RideStatusEnum;

/**
 * Class RideDTO
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Auto\Taxi\Application\Shared\DTOs
 */
final readonly class RideDTO
{
    public function __construct(
        public string $id,
        public int $clientId,
        public ?string $driverId,
        public RideStatusEnum $status,
        public array $pickupLocation,
        public array $dropoffLocation,
        public ?int $price,
        public string $createdAt,
        public string $updatedAt,
        public string $correlationId) {

    }
}
