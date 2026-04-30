<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\View;

final class TopProductsWidget extends Widget
{
    protected static string $view = 'filament.supermarket.widgets.top-products-widget';
    
    public function __construct(
        public string $period = '30d',
        public array $analytics = []
    ) {
        parent::__construct();
    }

    public function getViewData(): array
    {
        $topProducts = $this->analytics['top_products'] ?? collect();
        
        return [
            'topProducts' => $topProducts,
        ];
    }
}
