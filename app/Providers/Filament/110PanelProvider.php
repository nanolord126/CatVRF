<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

final class 110PanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('110')
            ->path('110')
            ->login()
            ->maxContentWidth('full');
    }
}
