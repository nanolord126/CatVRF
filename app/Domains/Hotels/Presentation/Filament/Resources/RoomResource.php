<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Filament\Resources;

use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class RoomResource extends Resource
{
    protected static ?string $model = RoomModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Hotels';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('hotel_id')
                    ->relationship('hotel', 'name')
                    ->required(),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price_per_night')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('capacity')
                    ->required()
                    ->numeric(),
                Forms\Components\KeyValue::make('amenities'),
                Forms\Components\Toggle::make('is_available')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotel.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_per_night')
                    ->money('rub')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => \App\Domains\Hotels\Presentation\Filament\Resources\RoomResource\Pages\ListRooms::route('/'),
            'create' => \App\Domains\Hotels\Presentation\Filament\Resources\RoomResource\Pages\CreateRoom::route('/create'),
            'edit' => \App\Domains\Hotels\Presentation\Filament\Resources\RoomResource\Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
