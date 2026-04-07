<?php declare(strict_types=1);

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
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Membership $membership,
        public readonly string     $correlationId) {}
}
