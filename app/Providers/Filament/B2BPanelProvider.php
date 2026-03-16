<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

final class B2BPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('b2b')
            ->path('b2b')
            ->login()
            ->maxContentWidth('full');
    }
}
