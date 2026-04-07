<?php

declare(strict_types=1);

/**
 * AssignRoleDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/assignroledto
 */


namespace App\Domains\Staff\Application\DTO\B2B;

use Spatie\LaravelData\Data;
use App\Domains\Staff\Domain\ValueObjects\StaffId;
use App\Domains\Staff\Domain\ValueObjects\RoleId;
use Ramsey\Uuid\UuidInterface;

/**
 * Class AssignRoleDTO
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
final readonly class AssignRoleDTO extends Data
{
    public function __construct(
        private readonly StaffId $staffId,
        private readonly RoleId $roleId,
        private readonly UuidInterface $tenantId,
        private readonly string $correlationId
    ) {

    }
}
