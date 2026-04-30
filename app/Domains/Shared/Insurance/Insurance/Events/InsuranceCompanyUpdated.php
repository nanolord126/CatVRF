<?php

declare(strict_types=1);

/**
 * InsuranceCompanyUpdated — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/insurancecompanyupdated
 */

namespace App\Domains\Shared\Insurance\Events;

use App\Domains\Shared\Insurance\Models\InsuranceCompany;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class InsuranceCompanyUpdated
 *
 * Part of the Insurance vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class InsuranceCompanyUpdated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly InsuranceCompany $insuranceCompany,
        private readonly string $correlationId
    ) {}
}
