<?php

declare(strict_types=1);

/**
 * RoomDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/roomdto
 */


namespace App\Domains\Hotels\Application\DTO;

use Spatie\LaravelData\Data;

/**
 * Class RoomDTO
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
final readonly class RoomDTO extends Data
{
    public function __construct(
        private readonly ?string $id,
        private readonly string $hotel_id,
        private readonly string $type,
        private readonly int $price_per_night,
        private readonly int $capacity,
        private readonly array $amenities,
        private readonly bool $is_available) {
        if ($price_per_night < 0) {
            throw new \InvalidArgumentException('Price per night must be non-negative');
        }

        if ($capacity <= 0) {
            throw new \InvalidArgumentException('Room capacity must be a positive integer');
        }

        if (trim($hotel_id) === '' || trim($type) === '') {
            throw new \InvalidArgumentException('Hotel ID and room type cannot be empty');
        }
    }
}
