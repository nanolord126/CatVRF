<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Domain\Enums\ConditionType;
use Modules\VetGrooming\Infrastructure\Models\PetChronicConditionModel;

class PetChronicConditionResource extends Resource
{
    protected static ?string $model = PetChronicConditionModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Медицинские записи';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о состоянии')
                    ->schema([
                        Forms\Components\Select::make('pet_id')
                            ->label('Питомец')
                            ->relationship('pet', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('condition_type')
                            ->label('Тип')
                            ->options(ConditionType::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => match ($state) {
                                ConditionType::ALLERGY_MEDICATION->value,
                                ConditionType::ALLERGY_FOOD->value,
                                ConditionType::ALLERGY_ENVIRONMENTAL->value => $set('severity', 'moderate'),
                                ConditionType::ANESTHESIA_INTOLERANCE->value => $set('severity', 'severe'),
                                default => null,
                            }),

                        Forms\Components\TextInput::make('condition_name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('icd_code')
                            ->label('Код МКБ')
                            ->helperText('Международный классификатор болезней для животных')
                            ->maxLength(50),

                        Forms\Components\DatePicker::make('diagnosed_date')
                            ->label('Дата диагноза')
                            ->default(now()),

                        Forms\Components\Select::make('veterinarian_id')
                            ->label('Ветеринар')
                            ->relationship('veterinarian', 'full_name')
                            ->searchable()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Детали')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),

                        Forms\Components\Select::make('severity')
                            ->label('Тяжесть')
                            ->options([
                                'mild' => 'Лёгкая',
                                'moderate' => 'Умеренная',
                                'severe' => 'Тяжёлая',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активно')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\DatePicker::make('resolved_date')
                            ->label('Дата разрешения')
                            ->visible(fn (callable $get) => ! $get('is_active')),
                    ]),

                Forms\Components\Section::make('Симптомы и триггеры')
                    ->schema([
                        Forms\Components\KeyValue::make('symptoms')
                            ->label('Симптомы')
                            ->keyLabel('Симптом')
                            ->valueLabel('Описание'),

                        Forms\Components\KeyValue::make('triggers')
                            ->label('Триггеры')
                            ->keyLabel('Триггер')
                            ->valueLabel('Примечание')
                            ->helperText('Что вызывает обострение (для аллергий)'),
                    ]),

                Forms\Components\Section::make('Управление')
                    ->schema([
                        Forms\Components\KeyValue::make('management_notes')
                            ->label('Заметки по управлению')
                            ->keyLabel('Категория')
                            ->valueLabel('Рекомендации')
                            ->helperText('Лечение, профилактика, особые указания'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pet.name')
                    ->label('Питомец')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('condition_type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ConditionType::ANESTHESIA_INTOLERANCE->value => 'danger',
                        ConditionType::ALLERGY_MEDICATION->value => 'warning',
                        ConditionType::CHRONIC_DISEASE->value => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('condition_name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('severity')
                    ->label('Тяжесть')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'severe' => 'danger',
                        'moderate' => 'warning',
                        'mild' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('diagnosed_date')
                    ->label('Диагностировано')
                    ->date(),

                Tables\Columns\TextColumn::make('resolved_date')
                    ->label('Разрешено')
                    ->date()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('veterinarian.full_name')
                    ->label('Ветеринар')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('condition_type')
                    ->label('Тип')
                    ->options(ConditionType::class),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные')
                    ->placeholder('Все')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только разрешённые'),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Тяжесть')
                    ->options([
                        'severe' => 'Тяжёлая',
                        'moderate' => 'Умеренная',
                        'mild' => 'Лёгкая',
                    ]),
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
}
