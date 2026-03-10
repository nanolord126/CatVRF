<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Services\Common\MarketplaceAISearchService;
use Livewire\Attributes\Url;

class PublicMarketplaceFacade extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Marketplace Admin';
    protected static ?string $title = 'Фасад Маркетплейса (Public Site)';
    protected static string $view = 'filament.tenant.pages.public-marketplace-facade';

    #[Url]
    public string $search = '';
    
    public array $filters = [
        'category' => 'all',
        'distance' => 5, // км
    ];

    public array $results = [];

    public function mount()
    {
        $this->loadResults();
    }

    public function updatedSearch()
    {
        $this->loadResults();
    }

    public function loadResults()
    {
        $this->results = (new MarketplaceAISearchService())
            ->unifiedSearch($this->search, $this->filters)
            ->toArray();
    }

    // Статистика фасада: Переходы, Конверсия, Популярные запросы
    public function getMarketStats(): array
    {
        return [
            'total_items' => 15430, // Цветы + Еда + Клиники + Такси
            'search_today' => 2450, // Число поисковых запросов через AI
            'conversion' => 12.5, // Процент заказов из поиска
            'top_query' => 'Розы ЦАО / Ветеринар Бутово'
        ];
    }
}
