<?php declare(strict_types=1);

namespace App\Domains\Fitness\Events;

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
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Fitness\Events
 */
final class MembershipPurchased
{
    use \Illuminate\Foundation\Events\Dispatchable, \Illuminate\Queue\SerializesModels;

    public function __construct(
        public readonly Membership $membership,
        public readonly string     $correlationId) {}
}

