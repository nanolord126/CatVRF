<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\UserTasteProfileChanged;
use App\Events\ProductInventoryChanged;
use App\Events\MasterAvailabilityChanged;
use App\Events\AIConstructorDesignSaved;
use App\Events\VerticalStatsRecalculated;
use App\Listeners\InvalidateUserTasteCacheListener;
use App\Listeners\InvalidateProductInventoryCacheListener;
use App\Listeners\InvalidateMasterAvailabilityCacheListener;
use App\Listeners\InvalidateAIConstructorCacheListener;
use App\Listeners\InvalidateVerticalStatsCacheListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class CacheInvalidationEventServiceProvider extends ServiceProvider
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

    public function boot(): void
    {
        parent::boot();
    }
}
