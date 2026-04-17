<?php declare(strict_types=1);

namespace App\Domains\Education\Filament\Resources;

use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\VerticalCourse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VerticalCourseResource extends Resource
{
    protected static ?string $model = VerticalCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Курсы по вертикалям';

    protected static ?string $modelLabel = 'Курс вертикали';

    protected static ?string $pluralModelLabel = 'Курсы по вертикалям';

    protected static ?string $navigationGroup = 'Образование';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Курс')
                            ->options(Course::all()->pluck('title', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('description', null)),

                        Forms\Components\Select::make('vertical')
                            ->label('Бизнес-вертикаль')
                            ->options([
                                'beauty' => 'Бьюти-салоны',
                                'hotels' => 'Гостиницы',
                                'flowers' => 'Флористика',
                                'auto' => 'Автосервис',
                                'medical' => 'Медицина',
                                'fitness' => 'Фитнес',
                                'restaurants' => 'Рестораны',
                                'pharmacy' => 'Аптеки',
                            ])
                            ->required(),

                        Forms\Components\Select::make('target_role')
                            ->label('Целевая роль')
                            ->options([
                                'manager' => 'Менеджер',
                                'specialist' => 'Специалист',
                                'administrator' => 'Администратор',
                                'receptionist' => 'Ресепшионист',
                                'master' => 'Мастер',
                                'florist' => 'Флорист',
                                'mechanic' => 'Механик',
                                'advisor' => 'Консультант',
                                'doctor' => 'Врач',
                                'nurse' => 'Медсестра',
                                'trainer' => 'Тренер',
                                'waiter' => 'Официант',
                                'chef' => 'Повар',
                                'pharmacist' => 'Провизор',
                                'assistant' => 'Помощник',
                                'housekeeper' => 'Горничная',
                                'concierge' => 'Консьерж',
                                'delivery' => 'Курьер',
                            ])
                            ->nullable(),

                        Forms\Components\Select::make('difficulty_level')
                            ->label('Уровень сложности')
                            ->options([
                                'beginner' => 'Начинающий',
                                'intermediate' => 'Средний',
                                'advanced' => 'Продвинутый',
                            ])
                            ->default('beginner')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Детали курса')
                    ->schema([
                        Forms\Components\TextInput::make('duration_hours')
                            ->label('Продолжительность (часы)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        Forms\Components\Toggle::make('is_required')
                            ->label('Обязательный курс')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Дополнительная информация')
                    ->schema([
                        Forms\Components\KeyValue::make('prerequisites')
                            ->label('Предварительные требования')
                            ->keyLabel('Требование')
                            ->valueLabel('Описание')
                            ->addable()
                            ->deletable()
                            ->nullable(),

                        Forms\Components\KeyValue::make('learning_objectives')
                            ->label('Цели обучения')
                            ->keyLabel('Цель')
                            ->valueLabel('Описание')
                            ->addable()
                            ->deletable()
                            ->nullable(),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Метаданные')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->addable()
                            ->deletable()
                            ->nullable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Курс')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('vertical')
                    ->label('Вертикаль')
                    ->colors([
                        'primary' => 'beauty',
                        'success' => 'hotels',
                        'warning' => 'flowers',
                        'danger' => 'auto',
                        'info' => 'medical',
                        'secondary' => 'fitness',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'beauty' => 'Бьюти',
                        'hotels' => 'Гостиницы',
                        'flowers' => 'Флористика',
                        'auto' => 'Авто',
                        'medical' => 'Медицина',
                        'fitness' => 'Фитнес',
                        'restaurants' => 'Рестораны',
                        'pharmacy' => 'Аптеки',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('target_role')
                    ->label('Роль')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('difficulty_level')
                    ->label('Сложность')
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'beginner' => 'Начинающий',
                        'intermediate' => 'Средний',
                        'advanced' => 'Продвинутый',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duration_hours')
                    ->label('Часов')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Обязательный')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'beauty' => 'Бьюти-салоны',
                        'hotels' => 'Гостиницы',
                        'flowers' => 'Флористика',
                        'auto' => 'Автосервис',
                        'medical' => 'Медицина',
                        'fitness' => 'Фитнес',
                        'restaurants' => 'Рестораны',
                        'pharmacy' => 'Аптеки',
                    ]),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('Сложность')
                    ->options([
                        'beginner' => 'Начинающий',
                        'intermediate' => 'Средний',
                        'advanced' => 'Продвинутый',
                    ]),

                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('Обязательный')
                    ->placeholder('Все')
                    ->trueLabel('Только обязательные')
                    ->falseLabel('Необязательные'),
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
            'index' => Pages\ListVerticalCourses::route('/'),
            'create' => Pages\CreateVerticalCourse::route('/create'),
            'edit' => Pages\EditVerticalCourse::route('/{record}/edit'),
        ];
    }
}
