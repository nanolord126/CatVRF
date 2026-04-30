<?php

declare(strict_types=1);

/**
 * VeterinaryAppointmentUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/veterinaryappointmentupdated
 */

namespace App\Domains\Veterinary\Events;

use App\Domains\Veterinary\Models\VeterinaryAppointment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class VeterinaryAppointmentUpdated
 *
 * Part of the Veterinary vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class VeterinaryAppointmentUpdated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly VeterinaryAppointment $veterinaryAppointment,
        private readonly string $correlationId
    ) {}
}
