<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics;

use App\Domains\Logistics\Models\PickupPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * PickupPointResource — Filament ресурс для управления ПВЗ
 *
 * Позволяет администраторам tenant'а управлять пунктами выдачи заказов.
 */
final class PickupPointResource extends Resource
{
    protected static ?string $model = PickupPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Logistics';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название ПВЗ'),

                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->rows(2)
                            ->label('Адрес'),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->label('Телефон'),
                    ]),

                Forms\Components\Section::make('Координаты')
                    ->schema([
                        Forms\Components\TextInput::make('lat')
                            ->required()
                            ->numeric()
                            ->step(0.000001)
                            ->label('Широта'),

                        Forms\Components\TextInput::make('lng')
                            ->required()
                            ->numeric()
                            ->step(0.000001)
                            ->label('Долгота'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Емкость и загрузка')
                    ->schema([
                        Forms\Components\TextInput::make('capacity_slots')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(100)
                            ->label('Вместимость (слотов)'),

                        Forms\Components\TextInput::make('current_load')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->label('Текущая загрузка'),

                        Forms\Components\Toggle::make('is_24h')
                            ->label('Круглосуточный'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                PickupPoint::STATUS_ACTIVE => 'Активен',
                                PickupPoint::STATUS_INACTIVE => 'Неактивен',
                                PickupPoint::STATUS_MAINTENANCE => 'На обслуживании',
                                PickupPoint::STATUS_CLOSED => 'Закрыт',
                            ])
                            ->required()
                            ->default(PickupPoint::STATUS_ACTIVE)
                            ->label('Статус'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активен'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Время работы')
                    ->schema([
                        Forms\Components\TextInput::make('working_hours')
                            ->placeholder('09:00-21:00')
                            ->helperText('Формат: ЧЧ:ММ-ЧЧ:ММ')
                            ->label('Часы работы'),
                    ])
                    ->visible(fn (Forms\Get $get) => ! $get('is_24h')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),

                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(30)
                    ->label('Адрес'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => PickupPoint::STATUS_ACTIVE,
                        'warning' => PickupPoint::STATUS_MAINTENANCE,
                        'danger' => PickupPoint::STATUS_CLOSED,
                        'gray' => PickupPoint::STATUS_INACTIVE,
                    ])
                    ->label('Статус'),

                Tables\Columns\TextColumn::make('current_load')
                    ->numeric()
                    ->suffix(' / ')
                    ->formatStateUsing(fn ($state, $record) => $state.' / '.$record->capacity_slots)
                    ->label('Загрузка'),

                Tables\Columns\TextColumn::make('load_percentage')
                    ->state(fn ($record) => $record->getLoadPercentage())
                    ->suffix('%')
                    ->color(fn ($state): string => match(true) {
                        $state > 85 => 'danger',
                        $state > 70 => 'warning',
                        default => 'success',
                    })
                    ->label('% загрузки'),

                Tables\Columns\IconColumn::make('is_24h')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-clock')
                    ->label('24/7'),

                Tables\Columns\TextColumn::make('working_hours')
                    ->toggleable()
                    ->label('Часы работы'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Создан'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PickupPoint::STATUS_ACTIVE => 'Активен',
                        PickupPoint::STATUS_INACTIVE => 'Неактивен',
                        PickupPoint::STATUS_MAINTENANCE => 'На обслуживании',
                        PickupPoint::STATUS_CLOSED => 'Закрыт',
                    ])
                    ->label('Статус'),

                Tables\Filters\TernaryFilter::make('is_24h')
                    ->label('Круглосуточные'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),
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
            ->defaultSort('name');
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
            'index' => Pages\ListPickupPoints::route('/'),
            'create' => Pages\CreatePickupPoint::route('/create'),
            'view' => Pages\ViewPickupPoint::route('/{record}'),
            'edit' => Pages\EditPickupPoint::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
