<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use App\Domains\Logistics\Models\PickupPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\Logistics\Filament\Resources\PickupPointResource\Pages\CreatePickupPoint;
use App\Domains\Logistics\Filament\Resources\PickupPointResource\Pages\EditPickupPoint;
use App\Domains\Logistics\Filament\Resources\PickupPointResource\Pages\ListPickupPoints;

/**
 * Pickup Point Filament Resource
 *
 * Admin interface for managing pickup points (ПВЗ).
 * Follows CatVRF Filament standards with tenant scoping.
 */
final class PickupPointResource extends Resource
{
    protected static ?string $model = PickupPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'ПВЗ';

    protected static ?string $modelLabel = 'Пункт выдачи';

    protected static ?string $pluralModelLabel = 'Пункты выдачи';

    protected static ?string $navigationGroup = 'Logistics';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Адрес')
                            ->required()
                            ->rows(2),

                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Геолокация')
                    ->schema([
                        Forms\Components\TextInput::make('lat')
                            ->label('Широта')
                            ->required()
                            ->numeric()
                            ->step(0.0000001)
                            ->min(-90)
                            ->max(90),

                        Forms\Components\TextInput::make('lng')
                            ->label('Долгота')
                            ->required()
                            ->numeric()
                            ->step(0.0000001)
                            ->min(-180)
                            ->max(180),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Емкость и часы работы')
                    ->schema([
                        Forms\Components\TextInput::make('capacity_slots')
                            ->label('Вместимость (слотов)')
                            ->required()
                            ->numeric()
                            ->min(1)
                            ->default(100),

                        Forms\Components\TextInput::make('current_load')
                            ->label('Текущая загрузка')
                            ->numeric()
                            ->min(0)
                            ->default(0)
                            ->helperText('Количество заказов на выдаче'),

                        Forms\Components\TextInput::make('working_hours')
                            ->label('Часы работы')
                            ->placeholder('Например: 09:00-21:00')
                            ->maxLength(20)
                            ->default('09:00-21:00'),

                        Forms\Components\Toggle::make('is_24h')
                            ->label('Круглосуточно')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активен',
                                'inactive' => 'Неактивен',
                                'maintenance' => 'На обслуживании',
                                'closed' => 'Закрыт',
                            ])
                            ->required()
                            ->default('active'),
                    ]),

                Forms\Components\Section::make('Метаданные')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Дополнительные параметры')
                            ->keyLabel('Параметр')
                            ->valueLabel('Значение')
                            ->reorderable()
                            ->addable()
                            ->deletable(),
                    ])
                    ->collapsible(),
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

                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'maintenance' => 'warning',
                        'closed' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('current_load')
                    ->label('Загрузка')
                    ->sortable()
                    ->formatStateUsing(
                        fn (PickupPoint $record): string => "{$record->current_load}/{$record->capacity_slots} (".number_format($record->getLoadPercentage(), 1).'%)'
                    )
                    ->color(
                        fn (PickupPoint $record): string => $record->isNearOverload() ? 'danger' : 'success'
                    ),

                Tables\Columns\IconColumn::make('is_24h')
                    ->label('24/7')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-moon'),

                Tables\Columns\TextColumn::make('working_hours')
                    ->label('Часы работы')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->toggleable(),

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
                        'active' => 'Активен',
                        'inactive' => 'Неактивен',
                        'maintenance' => 'На обслуживании',
                        'closed' => 'Закрыт',
                    ]),

                Tables\Filters\TernaryFilter::make('is_24h')
                    ->label('Круглосуточно')
                    ->placeholder('Все')
                    ->trueLabel('Да')
                    ->falseLabel('Нет'),

                Tables\Filters\Filter::make('near_overload')
                    ->label('Перегруженные (>85%)')
                    ->query(fn ($query) => $query->whereRaw('current_load > capacity_slots * 0.85')),
            ])
            ->actions([
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
            'index' => ListPickupPoints::route('/'),
            'create' => CreatePickupPoint::route('/create'),
            'edit' => EditPickupPoint::route('/{record}/edit'),
        ];
    }
}
