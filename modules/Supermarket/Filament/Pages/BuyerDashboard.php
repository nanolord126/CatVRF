<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Pages;

use App\Models\User;
use Modules\Supermarket\Infrastructure\Models\Subscription;
use Modules\Supermarket\Infrastructure\Models\SupermarketOrder;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Support\Facades\Auth;

class BuyerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Главная';
    protected static ?string $navigationGroup = 'Supermarket';
    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.supermarket.pages.buyer-dashboard';

    public array $activeSubscriptions = [];
    public array $recentOrders = [];
    public int $bonuses = 0;

    public function mount(): void
    {
        $buyer = Auth::user();
        
        $this->activeSubscriptions = Subscription::byBuyer($buyer->id)
            ->active()
            ->with(['items.product'])
            ->orderBy('next_delivery_at')
            ->limit(5)
            ->get()
            ->toArray();

        $this->recentOrders = SupermarketOrder::where('buyer_id', $buyer->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        $this->bonuses = $buyer->bonus_balance ?? 0;
    }

    public function getViewData(): array
    {
        return [
            'activeSubscriptions' => $this->activeSubscriptions,
            'recentOrders' => $this->recentOrders,
            'bonuses' => $this->bonuses,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverviewWidget::make([
                StatsOverviewWidget\Stat::make('Бонусы', number_format($this->bonuses, 0, ',', ' '))
                    ->description('Баланс бонусов')
                    ->icon('heroicon-o-gift'),

                StatsOverviewWidget\Stat::make('Подписки', count($this->activeSubscriptions))
                    ->description('Активных подписок')
                    ->icon('heroicon-o-arrows-right-left'),

                StatsOverviewWidget\Stat::make('Заказов', count($this->recentOrders))
                    ->description('Последних заказов')
                    ->icon('heroicon-o-shopping-bag'),
            ]),
        ];
    }
}
