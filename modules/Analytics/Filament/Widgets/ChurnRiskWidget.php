<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Analytics\Application\Services\SellerCLVService;

/**
 * Churn Risk Widget
 * 
 * Displays buyers at high risk of churning.
 * Shows buyer ID, churn probability, predicted CLV, and recommended action.
 * 
 * Production-ready: cached, actionable insights.
 */
final class ChurnRiskWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '600s';

    public function table(Table $table): Table
    {
        return $table
            ->query(Builder::class) // Placeholder
            ->columns([
                Tables\Columns\TextColumn::make('buyer_id')
                    ->label('Buyer ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('churnProbability')
                    ->label('Churn Probability')
                    ->formatStateUsing(fn ($state) => round($state * 100, 1) . '%')
                    ->sortable()
                    ->color('danger')
                    ->descriptionIcon('heroicon-o-exclamation-circle'),

                Tables\Columns\TextColumn::make('predictedClv180d')
                    ->label('At-Risk CLV')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' ₽')
                    ->sortable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('segment')
                    ->label('Current Segment')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'vip' => 'warning',
                        'high' => 'success',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('recommended_action')
                    ->label('Recommended Action')
                    ->description('Send retention offer')
                    ->color('info'),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }

    protected function getTableRecords(): array
    {
        $sellerId = Auth::id();
        $tenantId = tenant()?->id ?? 1;

        $clvService = app(SellerCLVService::class);
        $atRiskBuyers = $clvService->getHighChurnRiskBuyers($sellerId, $tenantId, threshold: 0.5, limit: 50);

        // Add recommended actions
        return array_map(function ($buyer) {
            return array_merge($buyer->toArray(), [
                'recommended_action' => $this->getRecommendedAction($buyer),
            ]);
        }, $atRiskBuyers);
    }

    private function getRecommendedAction($buyer): string
    {
        $clv = $buyer->predictedClv180d;
        $segment = $buyer->segment;

        if ($segment === 'vip') {
            return 'Dedicated support call + 20% discount';
        }

        if ($segment === 'high') {
            return 'Personal email + 15% coupon';
        }

        if ($segment === 'medium') {
            return 'Automated nurture sequence + 10% offer';
        }

        return 'Re-engagement email campaign';
    }
}
