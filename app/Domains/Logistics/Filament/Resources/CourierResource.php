<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use App\Domains\Logistics\Models\Courier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Courier Filament Resource
 *
 * Admin interface for managing couriers (unified fleet: couriers + taxi drivers).
 * Follows CatVRF Filament standards with tenant scoping.
 */
final class CourierResource extends Resource
{
    protected static ?string $model = Courier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Курьеры';

    protected static ?string $modelLabel = 'Курьер';

    protected static ?string $pluralModelLabel = 'Курьеры';

    protected static ?string $navigationGroup = 'Logistics';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('vehicle_type')
                            ->label('Тип транспорта')
                            ->options([
                                Courier::VEHICLE_PEDESTRIAN => 'Пешеход',
                                Courier::VEHICLE_BIKE => 'Велосипед',
                                Courier::VEHICLE_SCOOTER => 'Электросамокат',
                                Courier::VEHICLE_CAR => 'Автомобиль',
                                Courier::VEHICLE_TAXI => 'Такси (гибрид)',
                            ])
                            ->required()
                            ->default(Courier::VEHICLE_BIKE),

                        Forms\Components\Toggle::make('is_taxi_driver')
                            ->label('Таксист (гибридный режим)')
                            ->helperText('Может принимать как такси, так и курьерские заказы')
                            ->default(false),

                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицирован')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Статус и местоположение')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                Courier::STATUS_ONLINE => 'Онлайн',
                                Courier::STATUS_IDLE => 'Свободен',
                                Courier::STATUS_ON_DELIVERY => 'На доставке',
                                Courier::STATUS_OFFLINE => 'Офлайн',
                                Courier::STATUS_SUSPENDED => 'Приостановлен',
                                Courier::STATUS_BANNED => 'Заблокирован',
                            ])
                            ->required()
                            ->default(Courier::STATUS_OFFLINE),

                        Forms\Components\TextInput::make('current_lat')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.0000001)
                            ->min(-90)
                            ->max(90),

                        Forms\Components\TextInput::make('current_lng')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.0000001)
                            ->min(-180)
                            ->max(180),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Характеристики')
                    ->schema([
                        Forms\Components\TextInput::make('capacity_kg')
                            ->label('Грузоподъёмность (кг)')
                            ->required()
                            ->numeric()
                            ->min(0.1)
                            ->step(0.1)
                            ->default(10.0),

                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->min(0)
                            ->max(5)
                            ->step(0.1)
                            ->default(5.0),

                        Forms\Components\TextInput::make('delivery_count')
                            ->label('Количество доставок')
                            ->numeric()
                            ->min(0)
                            ->default(0)
                            ->disabled(),

                        Forms\Components\TextInput::make('battery_level')
                            ->label('Заряд батареи (%)')
                            ->numeric()
                            ->min(0)
                            ->max(100)
                            ->helperText('Для электротранспорта'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Финансы')
                    ->schema([
                        Forms\Components\TextInput::make('earnings_kopeki')
                            ->label('Заработок (копейки)')
                            ->numeric()
                            ->min(0)
                            ->default(0)
                            ->disabled()
                            ->helperText('Автоматический расчёт'),

                        Forms\Components\TextInput::make('commission_percent')
                            ->label('Комиссия (%)')
                            ->numeric()
                            ->min(0)
                            ->max(100)
                            ->default(10),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Метаданные')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Дополнительные параметры')
                            ->keyLabel('Параметр')
                            ->valueLabel('Значение')
                            ->reorderable()
                            ->addable()
                            ->deletable(),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Теги')
                            ->placeholder('Добавить тег')
                            ->suggestions([
                                'vip',
                                'express',
                                'night_shift',
                                'heavy_cargo',
                            ]),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Транспорт')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Courier::VEHICLE_PEDESTRIAN => 'gray',
                        Courier::VEHICLE_BIKE => 'info',
                        Courier::VEHICLE_SCOOTER => 'success',
                        Courier::VEHICLE_CAR => 'warning',
                        Courier::VEHICLE_TAXI => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Courier::VEHICLE_PEDESTRIAN => 'Пешеход',
                        Courier::VEHICLE_BIKE => 'Велосипед',
                        Courier::VEHICLE_SCOOTER => 'Электросамокат',
                        Courier::VEHICLE_CAR => 'Автомобиль',
                        Courier::VEHICLE_TAXI => 'Такси',
                    }),

                Tables\Columns\IconColumn::make('is_taxi_driver')
                    ->label('Таксист')
                    ->boolean()
                    ->trueIcon('heroicon-o-taxi')
                    ->falseIcon('heroicon-o-x-circle')
                    ->tooltip(
                        fn (Courier $record): string => $record->is_taxi_driver ? 'Гибридный режим' : 'Только курьер'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Courier::STATUS_ONLINE => 'success',
                        Courier::STATUS_IDLE => 'info',
                        Courier::STATUS_ON_DELIVERY => 'warning',
                        Courier::STATUS_OFFLINE => 'gray',
                        Courier::STATUS_SUSPENDED => 'warning',
                        Courier::STATUS_BANNED => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Courier::STATUS_ONLINE => 'Онлайн',
                        Courier::STATUS_IDLE => 'Свободен',
                        Courier::STATUS_ON_DELIVERY => 'На доставке',
                        Courier::STATUS_OFFLINE => 'Офлайн',
                        Courier::STATUS_SUSPENDED => 'Приостановлен',
                        Courier::STATUS_BANNED => 'Заблокирован',
                    }),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(
                        fn (float $state): string => number_format($state, 1).' ★'
                    )
                    ->color(
                        fn (float $state): string => $state >= 4.5 ? 'success' : ($state >= 3.5 ? 'warning' : 'danger')
                    ),

                Tables\Columns\TextColumn::make('capacity_kg')
                    ->label('Грузоподъёмность')
                    ->sortable()
                    ->formatStateUsing(fn (float $state): string => $state.' кг'),

                Tables\Columns\TextColumn::make('delivery_count')
                    ->label('Доставок')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('earnings_kopeki')
                    ->label('Заработок')
                    ->sortable()
                    ->formatStateUsing(
                        fn (Courier $record): string => number_format($record->getEarningsInRubles(), 2).' ₽'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_location_update')
                    ->label('Последнее обновление')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Courier::STATUS_ONLINE => 'Онлайн',
                        Courier::STATUS_IDLE => 'Свободен',
                        Courier::STATUS_ON_DELIVERY => 'На доставке',
                        Courier::STATUS_OFFLINE => 'Офлайн',
                        Courier::STATUS_SUSPENDED => 'Приостановлен',
                        Courier::STATUS_BANNED => 'Заблокирован',
                    ]),

                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->label('Тип транспорта')
                    ->options([
                        Courier::VEHICLE_PEDESTRIAN => 'Пешеход',
                        Courier::VEHICLE_BIKE => 'Велосипед',
                        Courier::VEHICLE_SCOOTER => 'Электросамокат',
                        Courier::VEHICLE_CAR => 'Автомобиль',
                        Courier::VEHICLE_TAXI => 'Такси',
                    ]),

                Tables\Filters\TernaryFilter::make('is_taxi_driver')
                    ->label('Таксисты')
                    ->placeholder('Все')
                    ->trueLabel('Только таксисты')
                    ->falseLabel('Только курьеры'),

                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Верификация')
                    ->placeholder('Все')
                    ->trueLabel('Верифицированные')
                    ->falseLabel('Неверифицированные'),

                Tables\Filters\Filter::make('available')
                    ->label('Доступные сейчас')
                    ->query(fn ($query) => $query->whereIn('status', [Courier::STATUS_ONLINE, Courier::STATUS_IDLE])
                        ->where('is_active', true)
                        ->where('is_verified', true)),

                Tables\Filters\Filter::make('low_battery')
                    ->label('Низкий заряд (<20%)')
                    ->query(fn ($query) => $query->where('battery_level', '<', 20)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCouriers::route('/'),
            'create' => Pages\CreateCourier::route('/create'),
            'edit' => Pages\EditCourier::route('/{record}/edit'),
        ];
    }
}
