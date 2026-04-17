<?php declare(strict_types=1);

/**
 * DemandForecastUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/demandforecastupdated
 */


namespace App\Domains\DemandForecast\Events;

use App\Domains\DemandForecast\Models\DemandForecast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class DemandForecastUpdated
 *
 * Part of the DemandForecast vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\DemandForecast\Events
 */
final class DemandForecastUpdated
{

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly DemandForecast $demandForecast,
        public readonly string $correlationId) {}
}
