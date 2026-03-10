<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\DeliveryZoneResource\Pages;
use Modules\Delivery\Models\DeliveryZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Delivery';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Delivery Zone Definition')
                    ->schema([
                        Components\TextInput::make('name')
                            ->placeholder('e.g. Central Zone')
                            ->required(),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextInput::make('center_lat')
                                    ->numeric()
                                    ->required(),
                                Components\TextInput::make('center_lng')
                                    ->numeric()
                                    ->required(),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextInput::make('radius_km')
                                    ->numeric()
                                    ->suffix('km')
                                    ->required(),
                                Components\TextInput::make('base_delivery_price')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->required(),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Columns\TextColumn::make('radius_km')
                    ->suffix(' km')
                    ->sortable(),
                Columns\TextColumn::make('base_delivery_price')
                    ->money('RUB')
                    ->sortable(),
                Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDeliveryZones::route('/'),
        ];
    }
}
