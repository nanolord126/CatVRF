<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\BehavioralEventResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Analytics\Models\BehavioralEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class BehavioralEventResource extends Resource
{
    protected static ?string $model = BehavioralEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Marketing AI';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->searchable(),
                BadgeColumn::make('event_type')->color(fn($state) => match($state) {
                    'order_completed' => 'success',
                    'booking_confirmed' => 'primary',
                    'view' => 'warning',
                    default => 'gray'
                }),
                TextColumn::make('vertical')->sortable(),
                TextColumn::make('monetary_value')->money()->sortable(),
                TextColumn::make('correlation_id')->label('Trace ID')->copyable(),
                TextColumn::make('occurred_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->options([
                        'Hotel' => 'Hotels',
                        'Beauty' => 'Beauty',
                        'Flowers' => 'Flowers',
                        'Taxi' => 'Taxi',
                        'Restaurant' => 'Restaurants'
                    ])
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBehavioralEvents::route('/'),
        ];
    }
}
