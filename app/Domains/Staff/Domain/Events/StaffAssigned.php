<?php

declare(strict_types=1);

/**
 * StaffAssigned — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/staffassigned
 */


namespace App\Domains\Staff\Domain\Events;

use App\Domains\Staff\Domain\ValueObjects\StaffId;
use App\Domains\Staff\Domain\ValueObjects\RoleId;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ramsey\Uuid\UuidInterface;

/**
 * Class StaffAssigned
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Staff\Domain\Events
 */
final class StaffAssigned
{
    use \Illuminate\Foundation\Events\Dispatchable, \Illuminate\Queue\SerializesModels;

    public function __construct(
        private readonly StaffId $staffId,
        private readonly RoleId $roleId,
        private readonly UuidInterface $tenantId,
        private readonly string $correlationId
    ) {

    }
}

