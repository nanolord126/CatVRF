<?php

declare(strict_types=1);

/**
 * StaffData — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/staffdata
 */


namespace App\Domains\Staff\Domain\DTOs;

use App\Domains\Staff\Domain\Enums\StaffRole;
use Spatie\LaravelData\Data;

/**
 * Class StaffData
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Staff\Domain\DTOs
 */
final class StaffData extends Data
{
    public function __construct(
        private readonly int $user_id,
        private readonly int $tenant_id,
        private readonly StaffRole $role,
        private readonly ?string $correlation_id) {

    }
}
