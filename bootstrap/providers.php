<?php

declare(strict_types=1);
use App\Domains\Education\Providers\EducationServiceProvider;
use App\Domains\Logistics\Providers\LogisticsServiceProvider;
use App\Domains\RealEstate\Application\Providers\RealEstateServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\CacheInvalidationEventServiceProvider;
use App\Providers\EventSystemServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\B2BPanelProvider;
use App\Providers\Filament\TenantPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\GeoLogisticsServiceProvider;
use App\Providers\OctaneServiceProvider;
use App\Providers\ProductionBootstrapServiceProvider;
use App\Providers\PrometheusServiceProvider;
use Laravel\Horizon\HorizonServiceProvider;
use Laravel\Pennant\PennantServiceProvider;
use Modules\CatCRM\Providers\StaffServiceProvider;
use Modules\Warehouse\Infrastructure\Providers\WarehouseServiceProvider;

$providers = [
    AppServiceProvider::class,
    ProductionBootstrapServiceProvider::class,
    CacheInvalidationEventServiceProvider::class,
    PrometheusServiceProvider::class,
    EventSystemServiceProvider::class,
    FortifyServiceProvider::class,
    GeoLogisticsServiceProvider::class,
    LogisticsServiceProvider::class,
    RealEstateServiceProvider::class,
    EducationServiceProvider::class,
    WarehouseServiceProvider::class,
    OctaneServiceProvider::class,
    HorizonServiceProvider::class,
    PennantServiceProvider::class,
];

// Отключаем Filament в тестовой среде
if (app()->environment('testing')) {
    return $providers;
}

// Отключено для миграций - Filament вызывает ошибку инициализации
// return array_merge($providers, [
//     AdminPanelProvider::class,
//     TenantPanelProvider::class,
//     B2BPanelProvider::class,
// ]);

return $providers;
