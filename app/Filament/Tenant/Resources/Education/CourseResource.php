<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education;

use Filament\Resources\Resource;

final class CourseResource extends Resource
{

    protected static ?string $model = Course::class;

        protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
        protected static ?string $navigationGroup = 'Education & LMS';
        protected static ?string $modelLabel = 'Курс';
        protected static ?string $pluralModelLabel = 'Курсы';

        /**
         * Форма создания/редактирования курса
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->label('Название курса'),

                            Forms\Components\RichEditor::make('description')
                                ->required()
                                ->maxLength(1000)
                                ->label('Описание'),

                            Forms\Components\Select::make('teacher_id')
                                ->relationship('teacher', 'full_name')
                                ->searchable()
                                ->required()
                                ->label('Преподаватель / Инструктор'),
                        ])->columns(2),

                    Forms\Components\Section::make('Финансы и Статус')
                        ->schema([
                            Forms\Components\TextInput::make('price_kopecks')
                                ->numeric()
                                ->required()
                                ->suffix('копеек')
                                ->label('Цена (в копейках)'),

                            Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => 'Черновик',
                                    'published' => 'Опубликован',
                                    'archived' => 'Архив',
                                ])
                                ->required()
                                ->default('draft')
                                ->label('Статус'),

                            Forms\Components\Select::make('level')
                                ->options([
                                    'beginner' => 'Начинающий',
                                    'intermediate' => 'Средний',
                                    'advanced' => 'Продвинутый',
                                ])
                                ->required()
                                ->label('Сложность'),
                        ])->columns(3),

                    Forms\Components\Section::make('Метаданные (КАНОН 2026)')
                        ->schema([
                            Forms\Components\TagsInput::make('tags')
                                ->label('Теги и Категории'),

                            Forms\Components\Hidden::make('correlation_id')
                                ->default(fn () => (string) Str::uuid()),
                        ]),
                ]);
        }

        /**
         * Таблица курсов
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('title')
                        ->searchable()
                        ->sortable()
                        ->label('Название'),

                    Tables\Columns\TextColumn::make('teacher.full_name')
                        ->label('Преподаватель'),

                    Tables\Columns\TextColumn::make('price_kopecks')
                        ->money('RUB', divideBy: 100)
                        ->label('Цена'),

                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'warning' => 'draft',
                            'success' => 'published',
                            'danger' => 'archived',
                        ])
                        ->label('Статус'),

                    Tables\Columns\TextColumn::make('enrollments_count')
                        ->counts('enrollments')
                        ->label('Студентов'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'draft' => 'Черновик',
                            'published' => 'Опубликован',
                            'archived' => 'Архив',
                        ]),
                    Tables\Filters\SelectFilter::make('level')
                        ->options([
                            'beginner' => 'Beginner',
                            'intermediate' => 'Intermediate',
                            'advanced' => 'Advanced',
                        ]),
                ])
                ->actions([
                    Tables\Actions\EditAction::make()
                        ->before(function (Course $record) {
                            // Fraud check before editing
                            app(FraudControlService::class)->checkOperation('edit_course', [
                                'course_id' => $record->id,
                            ]);
                        }),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\DeleteBulkAction::make(),
                ]);
        }

        public static function getRelations(): array
        {
            return [
                // ModulesRelationManager::class
            ];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Education\CourseResource\Pages\ListCourses::route('/'),
                'create' => \App\Filament\Tenant\Resources\Education\CourseResource\Pages\CreateCourse::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Education\CourseResource\Pages\EditCourse::route('/{record}/edit'),
            ];
        }

        /**
         * Тенант-скопинг через базовый запрос
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->with(['teacher', 'enrollments'])
                ->withoutGlobalScopes([
                    // Если нужно видеть удаленные soft-deletes
                ]);
        }
}
