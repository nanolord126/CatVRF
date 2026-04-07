<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

final class ProgramResource extends Resource
{

    protected static ?string $model = Program::class;

        protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

        protected static ?string $navigationGroup = 'Personal Development';

        protected static ?int $navigationSort = 2;

        /**
         * Построение формы редактирования программы.
         * Form > 70 строк
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Детали программы')
                        ->description('Общая информация и структура обучения')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок программы')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->placeholder('Космическая экспансия для руководителей'),

                            Forms\Components\Select::make('coach_id')
                                ->label('Ведущий программы (Коуч)')
                                ->relationship('coach', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\RichEditor::make('description')
                                ->label('Описание программы')
                                ->required()
                                ->columnSpanFull()
                                ->placeholder('Эта программа поможет вам стать...'),

                            Forms\Components\Select::make('type')
                                ->label('Тип программы')
                                ->options([
                                    'course' => 'Курс (многомодульный)',
                                    'workshop' => 'Мастер-класс (разовый)',
                                    'mentoring' => 'Менторство (индивидуально)',
                                ])
                                ->required()
                                ->default('course'),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Модули обучения')
                        ->description('Список уроков или этапов программы')
                        ->schema([
                            Forms\Components\Repeater::make('content_json')
                                ->label('Структура модулей')
                                ->schema([
                                    Forms\Components\TextInput::make('module_title')
                                        ->label('Название модуля')
                                        ->required(),

                                    Forms\Components\TextInput::make('duration_hours')
                                        ->label('Длительность (часы)')
                                        ->numeric()
                                        ->default(2),

                                    Forms\Components\RichEditor::make('module_summary')
                                        ->label('Краткое содержание')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['module_title'] ?? null)
                                ->addActionLabel('Добавить модуль')
                                ->cloneable()
                                ->columns(2),
                        ]),

                    Forms\Components\Section::make('Экономика и Доступ')
                        ->description('Стоимость обучения и финансовые настройки')
                        ->schema([
                            Forms\Components\TextInput::make('price_kopecks')
                                ->label('Стоимость программы (в копейках)')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(100)
                                ->suffix('коп.')
                                ->helperText('Сумма для разовой оплаты программы.'),

                            Forms\Components\Toggle::make('is_published')
                                ->label('Опубликовать программу')
                                ->default(true)
                                ->onIcon('heroicon-m-eye')
                                ->offIcon('heroicon-m-eye-slash')
                                ->helperText('Неопубликованная программа не отображается на витрине Marketplace.'),

                            Forms\Components\TextInput::make('category')
                                ->label('Категория (Тег)')
                                ->maxLength(255)
                                ->placeholder('Leadership'),
                        ])
                        ->columns(3),

                    Forms\Components\Section::make('Метаданные / Системные')
                        ->description('Технические данные')
                        ->collapsed()
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->label('UUID')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(fn () => (string) Str::uuid()),

                            Forms\Components\KeyValue::make('tags')
                                ->label('Дополнительные теги (JSON)')
                                ->keyLabel('Ключ')
                                ->valueLabel('Значение'),

                            Forms\Components\TextInput::make('correlation_id')
                                ->label('Correlation ID')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->columns(2),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListProgram::route('/'),
                'create' => Pages\CreateProgram::route('/create'),
                'edit' => Pages\EditProgram::route('/{record}/edit'),
                'view' => Pages\ViewProgram::route('/{record}'),
            ];
        }
}
