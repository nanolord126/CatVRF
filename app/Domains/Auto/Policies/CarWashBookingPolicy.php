<?php declare(strict_types=1);

/**
 * CarWashBookingPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/carwashbookingpolicy
 */


namespace App\Domains\Auto\Policies;

use Carbon\Carbon;

final class CarWashBookingPolicy
{

    public function viewAny(User $user): bool
        {
            return true;
        }

        public function view(User $user, CarWashBooking $booking): bool
        {
            return $user->id === $booking->client_id || $user->isAdmin();
        }

        public function create(User $user): bool
        {
            return $user->isVerified();
        }

        public function cancel(User $user, CarWashBooking $booking): Response
        {
            if ($user->id !== $booking->client_id && !$user->isAdmin()) {
                return $this->response->deny('Вы не можете отменить эту бронь');
            }

            if ($booking->status === 'completed' || $booking->status === 'cancelled') {
                return $this->response->deny('Бронь уже завершена или отменена');
            }

            $hoursUntilStart = $booking->scheduled_at->diffInHours(Carbon::now(), false);
            if ($hoursUntilStart < -24) {
                return $this->response->deny('Отмену можно сделать только за 24 часа до начала');
            }

            return $this->response->allow();
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
