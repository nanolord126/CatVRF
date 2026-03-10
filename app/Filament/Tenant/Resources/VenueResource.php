<?php

namespace App\Filament\Tenant\Resources;

use App\Models\MarketplaceVenue as Venue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;
    protected static ?string $navigationGroup = 'Events Module';
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $modelLabel = 'Площадка';
    protected static ?string $pluralModelLabel = 'Площадки и залы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Тип площадки')
                            ->options(Venue::getTypes())
                            ->reactive()
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('hotel_id')
                            ->label('Гостиница (привязка)')
                            ->options(fn() => class_exists('\Modules\Hotels\Models\Hotel') ? \Modules\Hotels\Models\Hotel::pluck('name', 'id') : [])
                            ->searchable()
                            ->visible(fn (callable $get) => $get('type') === Venue::TYPE_HOTEL_HALL),
                        Forms\Components\Select::make('restaurant_id')
                            ->label('Ресторан (привязка)')
                            ->options(fn() => class_exists('\App\Domains\Food\Models\Restaurant') ? \App\Domains\Food\Models\Restaurant::pluck('name', 'id') : [])
                            ->searchable()
                            ->visible(fn (callable $get) => $get('type') === Venue::TYPE_RESTAURANT_VIP),
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->required(),
                        Forms\Components\TextInput::make('capacity')
                            ->label('Вместимость')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Section::make('Геолокация')
                            ->schema([
                                Forms\Components\KeyValue::make('geo_location')
                                    ->label('Координаты (lat/lng)')
                            ])->collapsed(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => Venue::getTypes()[$state] ?? $state)
                    ->colors([
                        'primary' => Venue::TYPE_HOTEL_HALL,
                        'success' => [Venue::TYPE_BILLIARDS, Venue::TYPE_RESTAURANT_VIP],
                        'warning' => [Venue::TYPE_SAUNA, Venue::TYPE_BATHHOUSE],
                        'danger' => Venue::TYPE_CLUB,
                        'info' => Venue::TYPE_KARAOKE,
                    ]),
                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(30),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Мест')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип площадки')
                    ->options(Venue::getTypes()),
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
            'index' => VenueResource\Pages\ManageVenues::route('/'),
        ];
    }
}
