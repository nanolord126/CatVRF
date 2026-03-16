<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

final class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('app')
            ->login()
            ->maxContentWidth('full')
            ->middleware([
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ]);
    }
}
