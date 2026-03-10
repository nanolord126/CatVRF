<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\TaxiTripResource\Pages;
use App\Models\Tenants\TaxiTrip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TaxiTripResource extends Resource
{
    protected static ?string $model = TaxiTrip::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = '🛒 Marketplace';
    protected static ?string $modelLabel = 'Поездка на такси';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Детали Поездки')
                    ->schema([
                        Forms\Components\Select::make('passenger_id')
                            ->relationship('passenger', 'name')
                            ->required()
                            ->label('Пассажир'),
                        Forms\Components\Select::make('driver_id')
                            ->relationship('driver', 'name')
                            ->label('Водитель'),
                        Forms\Components\TextInput::make('origin_address')
                            ->required()
                            ->label('Точка А (Адрес)'),
                        Forms\Components\TextInput::make('destination_address')
                            ->required()
                            ->label('Точка Б (Адрес)'),
                        Forms\Components\TextInput::make('distance_km')
                            ->numeric()
                            ->label('Расстояние (км)')
                            ->suffix('км'),
                        Forms\Components\TextInput::make('fare')
                            ->numeric()
                            ->prefix('₽')
                            ->required()
                            ->label('Тариф (Итого)'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'searching' => 'Поиск водителя',
                                'accepted' => 'Принят',
                                'on_way' => 'Водитель едет',
                                'arrived' => 'Ожидание',
                                'in_trip' => 'В поездке',
                                'completed' => 'Завершен',
                                'cancelled' => 'Отменен',
                            ])
                            ->required()
                            ->label('Статус'),
                        Forms\Components\Hidden::make('correlation_id')
                            ->default(fn () => (string) Str::uuid()),
                    ])->columns(2),
                Forms\Components\Section::make('Геопозиция')
                    ->schema([
                        Forms\Components\KeyValue::make('origin_geo')->label('Координаты старта'),
                        Forms\Components\KeyValue::make('destination_geo')->label('Координаты финиша'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('№'),
                Tables\Columns\TextColumn::make('passenger.name')
                    ->label('Пассажир')
                    ->searchable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Водитель')
                    ->placeholder('Не назначен'),
                Tables\Columns\TextColumn::make('fare')
                    ->money('RUB')
                    ->label('Цена'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'searching',
                        'info' => ['accepted', 'on_way', 'arrived'],
                        'primary' => 'in_trip',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->label('Статус'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxiTrips::route('/'),
            'create' => Pages\CreateTaxiTrip::route('/create'),
            'edit' => Pages\EditTaxiTrip::route('/{record}/edit'),
        ];
    }
}
