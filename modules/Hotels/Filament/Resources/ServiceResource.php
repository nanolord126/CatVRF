<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hotels\Infrastructure\Models\ServiceModel;

final class ServiceResource extends Resource
{
    protected static ?string $model = ServiceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Услуги';
    protected static ?string $navigationGroup = 'Отели';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->relationship('venue', 'name')
                            ->searchable()
                            ->required()
                            ->label('Отель'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Название'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание'),
                        Forms\Components\Select::make('type')
                            ->options([
                                'breakfast' => 'Завтрак',
                                'transfer' => 'Трансфер',
                                'spa' => 'SPA',
                                'minibar' => 'Мини-бар',
                                'laundry' => 'Прачечная',
                                'room_service' => 'Room Service',
                                'parking' => 'Парковка',
                                'excursion' => 'Экскурсия',
                                'additional_bed' => 'Дополнительная кровать',
                                'late_checkout' => 'Поздний выезд',
                                'early_checkin' => 'Ранний заезд',
                            ])
                            ->required()
                            ->label('Тип услуги'),
                        Forms\Components\TextInput::make('icon')
                            ->label('Иконка'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Ценообразование')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->numeric()
                            ->prefix('₽')
                            ->required()
                            ->label('Базовая цена'),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'RUB' => 'Рубль',
                                'USD' => 'Доллар',
                                'EUR' => 'Евро',
                            ])
                            ->default('RUB')
                            ->required()
                            ->label('Валюта'),
                        Forms\Components\Toggle::make('is_available')
                            ->default(true)
                            ->label('Доступна'),
                        Forms\Components\Toggle::make('is_optional')
                            ->default(true)
                            ->label('Опциональная'),
                    ])
                    ->columns(4),
                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->label('Длительность (мин)'),
                        Forms\Components\TextInput::make('max_quantity_per_booking')
                            ->numeric()
                            ->label('Макс. кол-во на бронь'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->label('Порядок сортировки'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue.name')
                    ->label('Отель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'success' => 'breakfast',
                        'info' => 'transfer',
                        'purple' => 'spa',
                        'warning' => 'minibar',
                    ]),
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Цена')
                    ->money('rub'),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Доступна'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'breakfast' => 'Завтрак',
                        'transfer' => 'Трансфер',
                        'spa' => 'SPA',
                        'minibar' => 'Мини-бар',
                        'laundry' => 'Прачечная',
                        'room_service' => 'Room Service',
                        'parking' => 'Парковка',
                    ]),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Доступность'),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Hotels\Filament\Resources\ServiceResource\Pages\ListServices::route('/'),
            'create' => \Modules\Hotels\Filament\Resources\ServiceResource\Pages\CreateService::route('/create'),
            'view' => \Modules\Hotels\Filament\Resources\ServiceResource\Pages\ViewService::route('/{record}'),
            'edit' => \Modules\Hotels\Filament\Resources\ServiceResource\Pages\EditService::route('/{record}/edit'),
        ];
    }
}
