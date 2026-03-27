<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Filament\Resources;

use App\Domains\Education\Courses\Models\Course;
use Filament\Forms\Components\{Section, TextInput, Textarea, Select, Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{TextColumn, IconColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Actions\CreateAction;

final class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Обучение';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('title')
                            ->label('Название курса')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Описание')
                            ->required()
                            ->rows(4),
                        TextInput::make('category')
                            ->label('Категория')
                            ->required(),
                        Select::make('level')
                            ->label('Уровень')
                            ->options([
                                'beginner' => 'Начинающий',
                                'intermediate' => 'Средний',
                                'advanced' => 'Продвинутый',
                                'expert' => 'Эксперт',
                            ])
                            ->required(),
                    ]),
                Section::make('Детали курса')
                    ->schema([
                        TextInput::make('price')
                            ->label('Цена (копейки)')
                            ->numeric()
                            ->required(),
                        TextInput::make('duration_hours')
                            ->label('Продолжительность (часы)')
                            ->numeric()
                            ->required(),
                        TextInput::make('thumbnail_url')
                            ->label('URL миниатюры')
                            ->url(),
                    ]),
                Section::make('Статус')
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'published' => 'Опубликовано',
                                'archived' => 'Архивировано',
                            ]),
                        Toggle::make('is_published')
                            ->label('Опубликовано'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Категория'),
                BadgeColumn::make('level')
                    ->label('Уровень')
                    ->colors([
                        'gray' => 'beginner',
                        'info' => 'intermediate',
                        'warning' => 'advanced',
                        'danger' => 'expert',
                    ]),
                TextColumn::make('price')
                    ->label('Цена'),
                IconColumn::make('is_published')
                    ->label('Опубликовано')
                    ->boolean(),
                TextColumn::make('student_count')
                    ->label('Студентов'),
                TextColumn::make('rating')
                    ->label('Рейтинг'),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->options([
                        'beginner' => 'Начинающий',
                        'intermediate' => 'Средний',
                        'advanced' => 'Продвинутый',
                        'expert' => 'Эксперт',
                    ]),
                SelectFilter::make('is_published'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }
}
