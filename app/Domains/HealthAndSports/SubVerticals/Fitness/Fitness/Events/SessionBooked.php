<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\Fitness\Events;

use App\Domains\Fitness\Models\Session;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class SessionBooked
 *
 * Part of the Fitness vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class SessionBooked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Session $session,
        public readonly string $correlationId
    ) {}
}
