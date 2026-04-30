<?php

declare(strict_types=1);

/**
 * VeterinaryAppointmentCreated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/veterinaryappointmentcreated
 */

namespace App\Domains\Veterinary\Events;

use App\Domains\Veterinary\Models\VeterinaryAppointment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class VeterinaryAppointmentCreated
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
final class VeterinaryAppointmentCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly VeterinaryAppointment $veterinaryAppointment,
        private readonly string $correlationId
    ) {}
}
