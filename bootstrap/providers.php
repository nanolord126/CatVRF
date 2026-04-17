<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ProductionBootstrapServiceProvider::class,
    App\Providers\CacheInvalidationEventServiceProvider::class,
    App\Providers\PrometheusServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\TenantPanelProvider::class,
    App\Providers\Filament\B2BPanelProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\GeoLogisticsServiceProvider::class,
    App\Domains\RealEstate\Application\Providers\RealEstateServiceProvider::class,
    App\Domains\Education\Providers\EducationServiceProvider::class,
];
