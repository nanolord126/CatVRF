<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Analytics\Filament\Resources\SellerAnalyticsDashboardResource\Pages\ViewDashboard;
use Modules\Analytics\Models\SellerDailyMetrics;

/**
 * Seller Analytics Dashboard Resource
 *
 * Provides comprehensive analytics dashboard for sellers.
 * Includes KPI cards, trends, top products, and AI-powered insights.
 *
 * Protected by seller policy - only sellers can view their own analytics.
 */
final class SellerAnalyticsDashboardResource extends Resource
{
    protected static ?string $model = SellerDailyMetrics::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analytics Dashboard';

    protected static ?string $navigationGroup = 'Seller';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('orders_revenue')
                    ->label('Revenue')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('orders_aov')
                    ->label('AOV')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('conversion_rate')
                    ->label('Conversion')
                    ->percentage()
                    ->sortable(),

                Tables\Columns\TextColumn::make('seller_rating')
                    ->label('Rating')
                    ->numeric(2)
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewDashboard::route('/'),
        ];
    }

    /**
     * Get the label for the resource.
     */
    public static function getLabel(): string
    {
        return 'Seller Analytics';
    }

    /**
     * Get the plural label for the resource.
     */
    public static function getPluralLabel(): string
    {
        return 'Seller Analytics';
    }
}
