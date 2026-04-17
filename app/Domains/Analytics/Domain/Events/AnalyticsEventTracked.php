<?php

declare(strict_types=1);

/**
 * AnalyticsEventTracked — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/analyticseventtracked
 */


namespace App\Domains\Analytics\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AnalyticsEventTracked
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Analytics\Domain\Events
 */
final class AnalyticsEventTracked
{
    use \Illuminate\Foundation\Events\Dispatchable, \Illuminate\Queue\SerializesModels;

    public function __construct(
        public readonly string $eventType,
        public readonly array $payload,
        public readonly string $correlationId
    ) {
}
}

