<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionDemandForecast;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Tables;
use Filament\Tables\Table;

final class FashionDemandForecastResource extends BaseOptimizedResource
{
    protected static ?string $model = FashionDemandForecast::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Fashion Advanced';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tables\Columns\TextColumn::make('product_id'),
                Tables\Columns\TextColumn::make('forecast_data'),
                Tables\Columns\TextColumn::make('forecasted_at')->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('product_id')->searchable(),
                Tables\Columns\TextColumn::make('forecasted_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('forecasted_at', 'desc');
    }

    /**
     * Relations to eager load for Fashion
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
