<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use App\Models\B2BManufacturer;

class AIRecommendedSuppliersWidget extends BaseWidget
{
    protected static ?string $heading = 'AI-Recommended Suppliers (B2B Marketplace)';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                B2BManufacturer::query()
                    ->join('b2b_recommendations', 'b2b_manufacturers.id', '=', 'b2b_recommendations.manufacturer_id')
                    ->select('b2b_manufacturers.*', 'b2b_recommendations.match_score', 'b2b_recommendations.reasons')
                    ->orderByDesc('b2b_recommendations.match_score')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Manufacturer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge(),
                Tables\Columns\TextColumn::make('match_score')
                    ->label('AI Match %')
                    ->numeric(1)
                    ->color(fn (float $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('reasons')
                    ->label('AI Insights')
                    ->formatStateUsing(function ($state) {
                        $reasons = is_string($state) ? json_decode($state, true) : $state;
                        return collect($reasons)->join(' • ');
                    })
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_products')
                    ->url(fn (B2BManufacturer $record): string => "/tenant/b2b-partners") // Target partner resource
                    ->icon('heroicon-o-shopping-bag')
                    ->button(),
            ]);
    }
}
