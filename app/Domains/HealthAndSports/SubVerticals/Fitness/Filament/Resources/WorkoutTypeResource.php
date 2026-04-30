<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\WorkoutTypeModel;

final class WorkoutTypeResource extends Resource
{
    protected static ?string $model = WorkoutTypeModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Fitness CRM';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                        Forms\Components\Select::make('intensity')
                            ->options([
                                'very_low' => 'Очень низкая',
                                'low' => 'Низкая',
                                'moderate' => 'Средняя',
                                'high' => 'Высокая',
                                'very_high' => 'Очень высокая',
                            ])
                            ->required()
                            ->label('Интенсивность'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'strength' => 'Силовая',
                                'cardio' => 'Кардио',
                                'yoga' => 'Йога',
                                'pilates' => 'Пилатес',
                                'crossfit' => 'Кроссфит',
                                'boxing' => 'Бокс',
                                'dance' => 'Танцы',
                                'swimming' => 'Плавание',
                                'other' => 'Другое',
                            ])
                            ->label('Категория'),
                    ]),

                Forms\Components\Section::make('Параметры')
                    ->schema([
                        Forms\Components\TextInput::make('duration_minutes')
                            ->required()
                            ->numeric()
                            ->default(60)
                            ->suffix(' мин')
                            ->label('Длительность'),
                        Forms\Components\TextInput::make('calories_burn_estimate')
                            ->numeric()
                            ->default(400)
                            ->label('Расход калорий'),
                        Forms\Components\Toggle::make('is_group')
                            ->default(false)
                            ->label('Групповая тренировка'),
                        Forms\Components\TextInput::make('max_participants')
                            ->numeric()
                            ->default(1)
                            ->label('Макс. участников')
                            ->visible(fn (Forms\Get $get) => $get('is_group')),
                    ]),

                Forms\Components\Section::make('Оборудование')
                    ->schema([
                        Forms\Components\TagsInput::make('equipment_needed')
                            ->label('Необходимое оборудование')
                            ->suggestions([
                                'Гантели',
                                'Штанга',
                                'Тренажёры',
                                'Маты',
                                'Гантели',
                                'Эспандеры',
                            ]),
                    ]),

                Forms\Components\Section::make('Цена и оформление')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('₽')
                            ->default(0)
                            ->label('Цена'),
                        Forms\Components\TextInput::make('icon')
                            ->maxLength(50)
                            ->label('Иконка'),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Цвет'),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активен'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Название'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->label('Категория'),
                Tables\Columns\TextColumn::make('intensity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'very_low' => 'success',
                        'low' => 'success',
                        'moderate' => 'warning',
                        'high' => 'danger',
                        'very_high' => 'danger',
                    })
                    ->label('Интенсивность'),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->numeric()
                    ->suffix(' мин')
                    ->label('Длительность'),
                Tables\Columns\IconColumn::make('is_group')
                    ->boolean()
                    ->label('Групповая'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->label('Цена'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активен'),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Только активные'),
                Tables\Filters\Filter::make('is_group')
                    ->query(fn ($query) => $query->where('is_group', true))
                    ->label('Только групповые'),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'strength' => 'Силовая',
                        'cardio' => 'Кардио',
                        'yoga' => 'Йога',
                        'pilates' => 'Пилатес',
                        'crossfit' => 'Кроссфит',
                    ])
                    ->label('Категория'),
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
            'index' => \Modules\Fitness\Filament\Resources\WorkoutTypeResource\Pages\ListWorkoutTypes::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\WorkoutTypeResource\Pages\CreateWorkoutType::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\WorkoutTypeResource\Pages\EditWorkoutType::route('/{record}/edit'),
        ];
    }
}
