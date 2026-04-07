<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Presentation\Filament\Resources;

use App\Domains\GeoLogistics\Domain\Models\Shipment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use App\Domains\GeoLogistics\Domain\Enums\ShipmentStatus;

/**
 * Презентационный слой: Filament Admin для логистов.
 */
final class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    
    protected static ?string $navigationGroup = 'Логистика';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('uuid')->disabled(),
                Select::make('status')
                    ->options(array_column(ShipmentStatus::cases(), 'value', 'value'))
                    ->required(),
                TextInput::make('calculated_cost')
                    ->numeric()
                    ->disabled()
                    ->label('Стоимость (копейки)'),
                TextInput::make('estimated_distance_meters')
                    ->numeric()
                    ->disabled()
                    ->label('Дистанция (метры)'),
                TextInput::make('estimated_duration_seconds')
                    ->numeric()
                    ->disabled()
                    ->label('Время (секунды)'),
                // В полном проекте здесь будет: CustomMapField::make('route')->... 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')->limit(8)->searchable(),
                TextColumn::make('delivery_order_id')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ShipmentStatus $state): string => match ($state) {
                        ShipmentStatus::PENDING => 'gray',
                        ShipmentStatus::IN_TRANSIT => 'warning',
                        ShipmentStatus::DELIVERED => 'success',
                        ShipmentStatus::CANCELLED, ShipmentStatus::FAILED => 'danger',
                        default => 'primary',
                    }),
                TextColumn::make('calculated_cost')->money('RUB')->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\GeoLogistics\Presentation\Filament\Resources\ShipmentResource\Pages\ListShipments::route('/'),
            'edit' => \App\Domains\GeoLogistics\Presentation\Filament\Resources\ShipmentResource\Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}
