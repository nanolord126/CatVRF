<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\ExoticTrainingCourseModel;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;

final class ExoticTrainingCourseResource extends Resource
{
    protected static ?string $model = ExoticTrainingCourseModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Vet & Grooming';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о курсе')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Название курса'),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->label('Описание'),

                        Forms\Components\Select::make('exotic_category')
                            ->options([
                                'birds' => 'Птицы',
                                'reptiles' => 'Рептилии',
                                'small_mammals' => 'Мелкие млекопитающие',
                                'large_mammals' => 'Крупные млекопитающие',
                            ])
                            ->required()
                            ->label('Категория'),

                        Forms\Components\Select::make('exotic_group')
                            ->options([
                                'group_a' => 'Группа A (Высокий риск)',
                                'group_b' => 'Группа B (Средний риск)',
                                'group_c' => 'Группа C (Базовый уровень)',
                            ])
                            ->required()
                            ->label('Группа риска'),

                        Forms\Components\Select::make('certification_level')
                            ->options([
                                'certified' => 'Certified Exotic Groomer',
                                'advanced' => 'Advanced Exotic Groomer',
                                'master' => 'Master Exotic Groomer',
                            ])
                            ->required()
                            ->label('Уровень сертификации'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Детали курса')
                    ->schema([
                        Forms\Components\TextInput::make('duration_hours')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Длительность (часы)'),

                        Forms\Components\TextInput::make('passing_score')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(80)
                            ->label('Проходной балл (%)'),

                        Forms\Components\Toggle::make('is_mandatory')
                            ->default(false)
                            ->label('Обязательный курс'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активен'),

                        Forms\Components\Textarea::make('curriculum')
                            ->rows(5)
                            ->label('Учебная программа'),

                        Forms\Components\FileUpload::make('materials')
                            ->multiple()
                            ->directory('exotic-training/materials')
                            ->label('Учебные материалы'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('exotic_category')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'birds' => 'Птицы',
                        'reptiles' => 'Рептилии',
                        'small_mammals' => 'Мелкие млекопитающие',
                        'large_mammals' => 'Крупные млекопитающие',
                        default => $state,
                    })
                    ->label('Категория'),

                Tables\Columns\TextColumn::make('certification_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'certified' => 'success',
                        'advanced' => 'warning',
                        'master' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'certified' => 'Certified',
                        'advanced' => 'Advanced',
                        'master' => 'Master',
                        default => $state,
                    })
                    ->label('Уровень'),

                Tables\Columns\TextColumn::make('duration_hours')
                    ->label('Часы')
                    ->sortable(),

                Tables\Columns\TextColumn::make('passing_score')
                    ->label('Проходной балл')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_mandatory')
                    ->boolean()
                    ->label('Обязательный'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активен'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exotic_category')
                    ->options([
                        'birds' => 'Птицы',
                        'reptiles' => 'Рептилии',
                        'small_mammals' => 'Мелкие млекопитающие',
                        'large_mammals' => 'Крупные млекопитающие',
                    ])
                    ->label('Категория'),

                Tables\Filters\SelectFilter::make('certification_level')
                    ->options([
                        'certified' => 'Certified',
                        'advanced' => 'Advanced',
                        'master' => 'Master',
                    ])
                    ->label('Уровень'),

                Tables\Filters\TernaryFilter::make('is_mandatory')
                    ->label('Обязательный'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
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
            'index' => \Modules\VetGrooming\Filament\Resources\ExoticTrainingCourseResource\Pages\ListExoticTrainingCourses::route('/'),
            'create' => \Modules\VetGrooming\Filament\Resources\ExoticTrainingCourseResource\Pages\CreateExoticTrainingCourse::route('/create'),
            'edit' => \Modules\VetGrooming\Filament\Resources\ExoticTrainingCourseResource\Pages\EditExoticTrainingCourse::route('/{record}/edit'),
        ];
    }
}
