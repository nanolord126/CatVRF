<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Analytics\Models\DailyMetrics;

/**
 * Daily Metrics Filament Resource
 *
 * Admin interface for viewing and managing daily aggregated metrics.
 */
final class DailyMetricsResource extends Resource
{
    protected static ?string $model = DailyMetrics::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form schema if needed for editing
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->label('Date'),

                Tables\Columns\TextColumn::make('tenant_id')
                    ->sortable()
                    ->label('Tenant'),

                Tables\Columns\TextColumn::make('orders_count')
                    ->numeric()
                    ->sortable()
                    ->label('Orders'),

                Tables\Columns\TextColumn::make('orders_revenue')
                    ->money('RUB')
                    ->sortable()
                    ->label('Revenue'),

                Tables\Columns\TextColumn::make('orders_aov')
                    ->money('RUB')
                    ->sortable()
                    ->label('AOV'),

                Tables\Columns\TextColumn::make('users_active')
                    ->numeric()
                    ->sortable()
                    ->label('Active Users'),

                Tables\Columns\TextColumn::make('users_new')
                    ->numeric()
                    ->sortable()
                    ->label('New Users'),

                Tables\Columns\TextColumn::make('products_viewed')
                    ->numeric()
                    ->sortable()
                    ->label('Product Views'),

                Tables\Columns\TextColumn::make('sellers_active')
                    ->numeric()
                    ->sortable()
                    ->label('Active Sellers'),

                Tables\Columns\TextColumn::make('gmv')
                    ->money('RUB')
                    ->sortable()
                    ->label('GMV'),

                Tables\Columns\TextColumn::make('conversion_rate')
                    ->percentage()
                    ->sortable()
                    ->label('Conversion Rate'),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->where('date', '>=', $date))
                            ->when($data['to'], fn (Builder $query, $date) => $query->where('date', '<=', $date));
                    }),

                Tables\Filters\SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->label('Tenant'),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            // Define relationships if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Analytics\Filament\Resources\DailyMetricsResource\Pages\ListDailyMetrics::route('/'),
        ];
    }
}
