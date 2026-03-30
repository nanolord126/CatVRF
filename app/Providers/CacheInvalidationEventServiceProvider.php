<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CacheInvalidationEventServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
