<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class B2BDemandHeatmapWidget extends Widget
{
    protected static string $view = 'filament.widgets.b2b-demand-heatmap-widget';

    protected int | string | array $columnSpan = 'full';

    public function getHeatmapData(): array
    {
        // Mock data for demand trends per category
        return [
            ['category' => 'Animal Feed', 'intensity' => 0.8, 'growth' => '+12%'],
            ['category' => 'Medical Supplies', 'intensity' => 0.65, 'growth' => '+5%'],
            ['category' => 'Pharma', 'intensity' => 0.9, 'growth' => '+24%'],
            ['category' => 'Logistics', 'intensity' => 0.4, 'growth' => '-2%'],
        ];
    }
}
