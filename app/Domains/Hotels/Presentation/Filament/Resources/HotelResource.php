<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Filament\Resources;

use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Models\HotelModel;
use App\Filament\Resources\HotelResource\Pages;
use App\Filament\Resources\HotelResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class HotelResource extends Resource
{
    protected static ?string $model = HotelModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Hotels';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tenant_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('address')
                    ->required(),
                Forms\Components\KeyValue::make('amenities'),
                Forms\Components\TextInput::make('rating')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('correlation_id')
                    ->maxLength(255),
                Forms\Components\KeyValue::make('tags'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('correlation_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            RelationManagers\RoomsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Hotels\Presentation\Filament\Resources\HotelResource\Pages\ListHotels::route('/'),
            'create' => \App\Domains\Hotels\Presentation\Filament\Resources\HotelResource\Pages\CreateHotel::route('/create'),
            'edit' => \App\Domains\Hotels\Presentation\Filament\Resources\HotelResource\Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
