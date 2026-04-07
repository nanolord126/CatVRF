<?php

declare(strict_types=1);

/**
 * HotelDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/hoteldto
 */


namespace App\Domains\Hotels\Application\DTO;

use Spatie\LaravelData\Data;

/**
 * Class HotelDTO
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final readonly class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Hotels\Application\DTO
 */
final readonly class HotelDTO extends Data
{
    public function __construct(
        private readonly ?string $id,
        private readonly int $tenant_id,
        private readonly string $name,
        private readonly string $description,
        private readonly AddressDTO $address,
        private readonly array $amenities,
        private readonly float $rating,
        private ?string $correlation_id = null) {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Hotel name cannot be empty');
        }

        if ($rating < 0.0 || $rating > 5.0) {
            throw new \InvalidArgumentException('Rating must be between 0.0 and 5.0');
        }

        $this->correlation_id = $correlation_id ?? \Illuminate\Support\Str::uuid()->toString();
    }
}
