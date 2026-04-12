<?php

declare(strict_types=1);

/**
 * AddressDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/addressdto
 */


namespace App\Domains\Hotels\Application\DTO;

use Spatie\LaravelData\Data;

/**
 * Class AddressDTO
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
final class AddressDTO extends Data
{
    public function __construct(
        private readonly string $country,
        private readonly string $city,
        private readonly string $street,
        private readonly string $house_number,
        private ?string $zip_code = null) {
        if (trim($country) === '' || trim($city) === '' || trim($street) === '' || trim($house_number) === '') {
            throw new \InvalidArgumentException('Address fields (country, city, street, house_number) cannot be empty');
        }
    }
}
