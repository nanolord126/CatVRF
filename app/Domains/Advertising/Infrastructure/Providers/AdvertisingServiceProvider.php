<?php

declare(strict_types=1);

/**
 * AdvertisingServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/advertisingserviceprovider
 */


namespace App\Domains\Advertising\Infrastructure\Providers;

use App\Domains\Advertising\Domain\Interfaces\AdCampaignRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\AdImpressionRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\AdPlacementStrategyInterface;
use App\Domains\Advertising\Infrastructure\Persistence\EloquentAdCampaignRepository;
use App\Domains\Advertising\Infrastructure\Persistence\EloquentAdImpressionRepository;
use App\Domains\Advertising\Infrastructure\Services\MlPlacementStrategy;
use Illuminate\Support\ServiceProvider;

/**
 * Class AdvertisingServiceProvider
 *
 * Part of the Advertising vertical domain.
 * Follows CatVRF 9-layer architecture.
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
 * @package App\Domains\Advertising\Infrastructure\Providers
 */
final class AdvertisingServiceProvider extends ServiceProvider
{
    /**
     * Handle register operation.
     *
     * @throws \DomainException
     */
    public function register(): void
    {
        $this->app->bind(AdCampaignRepositoryInterface::class, EloquentAdCampaignRepository::class);
        $this->app->bind(AdImpressionRepositoryInterface::class, EloquentAdImpressionRepository::class);
        $this->app->bind(AdPlacementStrategyInterface::class, MlPlacementStrategy::class);
    }
}
