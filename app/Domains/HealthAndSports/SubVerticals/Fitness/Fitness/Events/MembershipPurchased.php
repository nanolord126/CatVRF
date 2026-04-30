<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\Fitness\Events;

use App\Domains\Fitness\Models\Membership;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class MembershipPurchased
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
final class MembershipPurchased
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Membership $membership,
        public readonly string $correlationId
    ) {}
}
