<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Services\Common\GlobalAIBusinessForecastingService;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\B2B\PurchaseOrder;
use App\Models\HR\HRExchangeTask;

class GlobalBusinessDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Admin Intelligence';
    protected static ?string $title = 'Global Ecosystem 2026 Admin';
    protected static ?int $navigationSort = -100;
    protected static string $view = 'filament.tenant.pages.global-business-dashboard';

    public array $forecast = [];

    public function mount()
    {
        $this->forecast = (new GlobalAIBusinessForecastingService())->getGlobalForecast();
    }
}
