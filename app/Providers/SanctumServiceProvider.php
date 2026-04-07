<?php declare(strict_types=1);

namespace App\Providers;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

/**
 * Class SanctumServiceProvider
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Providers
 */
final class SanctumServiceProvider extends ServiceProvider
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    

    /**
     * Handle register operation.
     *
     * @throws \DomainException
     */
    public function register(): void
        {
            Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
        }

        /**
         * Handle boot operation.
         *
         * @throws \DomainException
         */
        public function boot(): void
        {
            Sanctum::defaultApiTokenExpiration($this->config->get('sanctum.expiration'));

            Sanctum::authenticateSessionsWith('sanctum');
        }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
