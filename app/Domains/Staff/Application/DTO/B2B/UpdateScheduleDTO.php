<?php

declare(strict_types=1);

/**
 * UpdateScheduleDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/updatescheduledto
 */


namespace App\Domains\Staff\Application\DTO\B2B;

use Spatie\LaravelData\Data;
use App\Domains\Staff\Domain\ValueObjects\StaffId;
use Carbon\Carbon;
use Ramsey\Uuid\UuidInterface;

/**
 * Class UpdateScheduleDTO
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
 * @package App\Domains\Staff\Application\DTO\B2B
 */
final class UpdateScheduleDTO extends Data
{
    public function __construct(
        private readonly StaffId $staffId,
        private readonly Carbon $startTime,
        private readonly Carbon $endTime,
        private readonly UuidInterface $tenantId,
        private readonly string $correlationId
    ) {

    }
}
