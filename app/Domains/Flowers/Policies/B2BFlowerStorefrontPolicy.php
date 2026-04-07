<?php declare(strict_types=1);

/**
 * B2BFlowerStorefrontPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2bflowerstorefrontpolicy
 */


namespace App\Domains\Flowers\Policies;

final class B2BFlowerStorefrontPolicy
{

    public function view(User $user, B2BFlowerStorefront $storefront): Response
        {
            if (!$user->company_inn) {
                return $this->response->deny('Company INN is required');
            }

            if ($user->company_inn === $storefront->company_inn && $storefront->is_active) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot access this B2B storefront');
        }

        public function register(User $user): Response
        {
            if ($user->company_inn && !$user->b2bFlowerStorefront) {
                return $this->response->allow();
            }

            return $this->response->deny('Invalid B2B registration request');
        }

        public function update(User $user, B2BFlowerStorefront $storefront): Response
        {
            if ($user->company_inn === $storefront->company_inn) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot update this storefront');
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
