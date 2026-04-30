<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Application\Services\SellerAnalyticsService;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Insights Widget
 *
 * Displays AI-powered insights and recommendations for the seller.
 * Shows revenue drops, price optimization opportunities, inventory alerts, etc.
 */
class InsightsWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'AI Insights';

    public array $insights = [];

    public function mount(): void
    {
        $this->loadInsights();
    }

    /**
     * Load insights from analytics service.
     */
    public function loadInsights(): void
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? 1;

        if (!$sellerId) {
            return;
        }

        $period = Period::last30Days();
        $insights = app(SellerAnalyticsService::class)
            ->generateInsights($sellerId, $period, $tenantId);

        $this->insights = array_map(fn ($insight) => $insight->toArray(), $insights);
    }

    /**
     * Render the widget.
     */
    public function render(): View
    {
        return view('filament.analytics.widgets.insights', [
            'insights' => $this->insights,
        ]);
    }

    /**
     * Get the widget refresh interval in seconds.
     */
    protected static function getRefreshInterval(): ?int
    {
        return 900; // Refresh every 15 minutes
    }
}
