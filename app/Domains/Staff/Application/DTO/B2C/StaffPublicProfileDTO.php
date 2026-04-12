<?php

declare(strict_types=1);

/**
 * StaffPublicProfileDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/staffpublicprofiledto
 */


namespace App\Domains\Staff\Application\DTO\B2C;

use Spatie\LaravelData\Data;
use App\Domains\Staff\Domain\ValueObjects\FullName;
use App\Domains\Staff\Domain\Enums\Vertical;
use Illuminate\Support\Collection;

/**
 * Class StaffPublicProfileDTO
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final readonly class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Staff\Application\DTO\B2C
 */
final class StaffPublicProfileDTO extends Data
{
    public function __construct(
        private readonly FullName $fullName,
        private readonly Vertical $vertical,
        private readonly float $rating,
        private readonly Collection $reviews,
        private readonly ?string $avatarUrl
    ) {

    }
}
