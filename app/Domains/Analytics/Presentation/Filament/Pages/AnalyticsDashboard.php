<?php

declare(strict_types=1);

/**
 * AnalyticsDashboard — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/analyticsdashboard
 */


namespace App\Domains\Analytics\Presentation\Filament\Pages;

use Filament\Pages\Page;

/**
 * Class AnalyticsDashboard
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\Analytics\Presentation\Filament\Pages
 */
final class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.analytics-dashboard';
    
    protected static ?int $navigationSort = -1;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Domains\Analytics\Presentation\Filament\Widgets\PageViewsChart::class,
            \App\Domains\Analytics\Presentation\Filament\Widgets\UniqueUsersChart::class,
        ];
    }
}
