<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\HotelResource\Pages;
use App\Filament\Tenant\Resources\HotelResource\RelationManagers;
use Modules\Hotels\Models\Hotel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('category')
                    ->options([
                        'hotel' => 'Гостиница',
                        'country_house' => 'Загородный дом',
                        'resort' => 'Санаторий',
                        'hostel' => 'Хостел',
                        'apartment' => 'Апартаменты',
                        'guest_house' => 'Гостевой дом',
                    ])
                    ->required(),
                Select::make('stars')
                    ->options([
                        1 => '1 Звезда',
                        2 => '2 Звезды',
                        3 => '3 Звезды',
                        4 => '4 Звезды',
                        5 => '5 Звезд',
                    ])
                    ->nullable(),
                TextInput::make('address')
                    ->maxLength(255),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('stars')->numeric(),
                TextColumn::make('address'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
