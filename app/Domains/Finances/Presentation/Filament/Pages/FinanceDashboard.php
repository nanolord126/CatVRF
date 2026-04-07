<?php

declare(strict_types=1);

namespace App\Domains\Finances\Presentation\Filament\Pages;

use App\Domains\Finances\Presentation\Filament\Widgets\PayoutsOverview;
use App\Domains\Finances\Presentation\Filament\Widgets\RevenueChart;
use Filament\Pages\Page;

/**
 * Дашборд финансовой вертикали.
 *
 * Отображает основные метрики: выручка, выплаты,
 * комиссии, балансы. Tenant-scoped: данные
 * фильтруются по текущему тенанту.
 *
 * @package App\Domains\Finances\Presentation\Filament\Pages
 */
final class FinanceDashboard extends Page
{
    /**
     * Иконка в навигации.
     */
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    /**
     * Blade-вью страницы.
     */
    protected static string $view = 'filament.pages.finance-dashboard';

    /**
     * Порядок в навигации.
     */
    protected static ?int $navigationSort = 0;

    /**
     * Группа навигации.
     */
    protected static ?string $navigationGroup = 'Finances';

    /**
     * Заголовок страницы.
     */
    protected static ?string $title = 'Финансовый дашборд';

    /**
     * Метка в навигации.
     */
    protected static ?string $navigationLabel = 'Дашборд';

    /**
     * Виджеты в шапке страницы.
     *
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            RevenueChart::class,
            PayoutsOverview::class,
        ];
    }

    /**
     * Количество колонок для виджетов.
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    /**
     * Получить slug для URL.
     */
    public static function getSlug(): string
    {
        return 'finances/dashboard';
    }
}
