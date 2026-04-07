<?php declare(strict_types=1);

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
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Fitness\Events
 */
final class SessionBooked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Session $session,
        public readonly string  $correlationId) {}
}
