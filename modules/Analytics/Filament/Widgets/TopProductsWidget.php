<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Application\Services\SellerAnalyticsService;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Top Products Widget
 *
 * Displays top products by revenue for the seller.
 * Shows product name, revenue, orders, and conversion rate.
 */
class TopProductsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 2;

    protected static ?string $heading = 'Top Products';

    public function table(Table $table): Table
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? 1;

        if (!$sellerId) {
            return $table
                ->query(\Modules\Analytics\Models\ProductMetrics::query()->whereRaw('1=0'))
                ->columns([]);
        }

        $period = Period::last30Days();
        $topProducts = app(SellerAnalyticsService::class)
            ->getTopProducts($sellerId, $period, $tenantId, 10);

        $items = $topProducts->items ?? [];

        return $table
            ->query(
                \Modules\Analytics\Models\ProductMetrics::query()
                    ->whereIn('product_id', array_column($items, 'id'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('product_id')
                    ->label('Product')
                    ->formatStateUsing(fn ($state) => "Product #{$state}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('revenue')
                    ->label('Revenue')
                    ->money('usd')
                    ->sortable()
                    ->getStateUsing(function ($record) use ($items) {
                        $item = collect($items)->firstWhere('id', $record->product_id);
                        return $item['value'] ?? 0;
                    }),

                Tables\Columns\TextColumn::make('orders')
                    ->label('Orders')
                    ->numeric()
                    ->sortable()
                    ->getStateUsing(function ($record) use ($items) {
                        $item = collect($items)->firstWhere('id', $record->product_id);
                        return $item['metadata']['orders'] ?? 0;
                    }),

                Tables\Columns\TextColumn::make('conversion_rate')
                    ->label('Conversion')
                    ->percentage()
                    ->sortable()
                    ->getStateUsing(function ($record) use ($items) {
                        $item = collect($items)->firstWhere('id', $record->product_id);
                        return $item['metadata']['conversion_rate'] ?? 0;
                    }),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }

    /**
     * Get the widget refresh interval in seconds.
     */
    protected static function getRefreshInterval(): ?int
    {
        return 600; // Refresh every 10 minutes
    }
}
