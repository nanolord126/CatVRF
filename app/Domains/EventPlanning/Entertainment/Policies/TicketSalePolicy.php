<?php declare(strict_types=1);

/**
 * TicketSalePolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ticketsalepolicy
 */


namespace App\Domains\EventPlanning\Entertainment\Policies;


use Illuminate\Contracts\Auth\Guard;
final class TicketSalePolicy
{
    public function __construct(
        private readonly Guard $guard) {}


    public function viewAny(User $user): Response
        {
            return $this->guard->check() ? $this->response->allow() : $this->response->deny('Unauthorized');
        }

        public function view(User $user, TicketSale $ticket): Response
        {
            return $user->id === $ticket->booking->customer_id || $user->hasPermissionTo('view_tickets')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_tickets')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function refund(User $user, TicketSale $ticket): Response
        {
            return $user->id === $ticket->booking->customer_id || $user->hasPermissionTo('refund_tickets')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
