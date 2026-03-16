<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

final class CRMPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('crm')
            ->path('crm')
            ->login()
            ->maxContentWidth('full');
    }
}
