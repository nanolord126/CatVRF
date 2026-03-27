<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\ShortTermRentals\Models\StrApartment;
use App\Filament\Tenant\Resources\StrApartmentResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class StrApartmentResource extends Resource
{
    protected static ?string $model = StrApartment::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Short-Term Rentals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Main Info')
                    ->schema([
                        Forms\Components\Select::make('str_property_id')
                            ->relationship('property', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('room_number')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\Select::make('type')
                            ->options([
                                'studio' => 'Студия',
                                'apartment' => 'Апартаменты',
                                'room' => 'Комната',
                                'villa' => 'Вилла',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing & Capacity')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->numeric()
                            ->prefix('RUB')
                            ->required()
                            ->helperText('Цена в копейках'),
                        Forms\Components\TextInput::make('deposit_amount')
                            ->numeric()
                            ->prefix('RUB')
                            ->required()
                            ->helperText('Залог в копейках'),
                        Forms\Components\TextInput::make('max_guests')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('total_rooms')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Meta')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('Добавьте теги для аналитики'),
                        Forms\Components\KeyValue::make('metadata')
                            ->helperText('Дополнительные параметры (удобства, правила)'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('property.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('room_number'),
                Tables\Columns\TextColumn::make('base_price')
                    ->money('RUB')
                    ->description(fn (StrApartment $record) => "Deposit: " . number_format($record->deposit_amount / 100, 2) . " ₽")
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property')
                    ->relationship('property', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrApartments::route('/'),
            'create' => Pages\CreateStrApartment::route('/create'),
            'edit' => Pages\EditStrApartment::route('/{record}/edit'),
        ];
    }
}
