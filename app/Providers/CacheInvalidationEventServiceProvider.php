<?php declare(strict_types=1);

namespace App\Providers;

use App\Events\AIConstructorDesignSaved;
use App\Events\MasterAvailabilityChanged;
use App\Events\ProductInventoryChanged;
use App\Events\UserTasteProfileChanged;
use App\Events\VerticalStatsRecalculated;
use App\Listeners\InvalidateAIConstructorCacheListener;
use App\Listeners\InvalidateMasterAvailabilityCacheListener;
use App\Listeners\InvalidateProductInventoryCacheListener;
use App\Listeners\InvalidateUserTasteCacheListener;
use App\Listeners\InvalidateVerticalStatsCacheListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

/**
 * Class CacheInvalidationEventServiceProvider
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
final class CacheInvalidationEventServiceProvider extends EventServiceProvider
{
    protected $listen = [
        UserTasteProfileChanged::class => [
            InvalidateUserTasteCacheListener::class,
        ],
        ProductInventoryChanged::class => [
            InvalidateProductInventoryCacheListener::class,
        ],
        MasterAvailabilityChanged::class => [
            InvalidateMasterAvailabilityCacheListener::class,
        ],
        AIConstructorDesignSaved::class => [
            InvalidateAIConstructorCacheListener::class,
        ],
        VerticalStatsRecalculated::class => [
            InvalidateVerticalStatsCacheListener::class,
        ],
    ];

    /**
     * Handle boot operation.
     *
     * @throws \DomainException
     */
    public function boot(): void
    {
        parent::boot();
    }
}
