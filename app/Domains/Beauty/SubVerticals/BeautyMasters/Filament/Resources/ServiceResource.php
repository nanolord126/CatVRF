<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\BeautyMasters\Infrastructure\Models\ServiceModel;

final class ServiceResource extends Resource
{
    protected static ?string $model = ServiceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    protected static ?string $navigationLabel = 'Услуги';

    protected static ?string $modelLabel = 'Услуга';

    protected static ?string $pluralModelLabel = 'Услуги';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->label('Салон')
                            ->relationship('venue', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Длительность и цена')
                    ->schema([
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Длительность (мин)')
                            ->required()
                            ->numeric()
                            ->minValue(5)
                            ->default(30),

                        Forms\Components\TextInput::make('buffer_minutes')
                            ->label('Буфер (мин)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Время между записями'),

                        Forms\Components\TextInput::make('price')
                            ->label('Цена')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->minValue(0),

                        Forms\Components\TextInput::make('discount_price')
                            ->label('Цена со скидкой')
                            ->numeric()
                            ->prefix('₽')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),

                        Forms\Components\Toggle::make('requires_photo_before')
                            ->label('Фото до')
                            ->default(false)
                            ->helperText('Требуется фото до процедуры'),

                        Forms\Components\Toggle::make('requires_photo_after')
                            ->label('Фото после')
                            ->default(false)
                            ->helperText('Требуется фото после процедуры'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Расходники')
                    ->schema([
                        Forms\Components\TagsInput::make('required_supplies')
                            ->label('Необходимые расходники')
                            ->suggestions([
                                'Шампунь',
                                'Лак',
                                'Краска',
                                'Пудра',
                                'Кисти',
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('venue.name')
                    ->label('Салон')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Длительность')
                    ->sortable()
                    ->suffix(' мин'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('final_price')
                    ->label('Финальная цена')
                    ->money('RUB')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('venue_id')
                    ->label('Салон')
                    ->relationship('venue', 'name'),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
