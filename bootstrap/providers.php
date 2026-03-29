<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ProductionBootstrapServiceProvider::class,
    App\Providers\CacheInvalidationEventServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\TenantPanelProvider::class,
    App\Providers\FortifyServiceProvider::class,
];
