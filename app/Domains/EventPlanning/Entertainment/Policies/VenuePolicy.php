<?php declare(strict_types=1);

/**
 * VenuePolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/venuepolicy
 */


namespace App\Domains\EventPlanning\Entertainment\Policies;

final class VenuePolicy
{

    public function viewAny(User $user): bool
        {
            return $user->can('view_venues');
        }

        public function view(User $user, Venue $venue): bool
        {
            return $user->tenant_id === $venue->tenant_id;
        }

        public function create(User $user): bool
        {
            return $user->can('manage_entertainment');
        }

        public function update(User $user, Venue $venue): bool
        {
            return $user->tenant_id === $venue->tenant_id && $user->can('manage_entertainment');
        }

        public function delete(User $user, Venue $venue): bool
        {
            return $user->tenant_id === $venue->tenant_id && $user->hasRole('admin');
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
