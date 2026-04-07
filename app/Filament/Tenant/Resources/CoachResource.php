<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class CoachResource extends Resource
{

    protected static ?string $model = Coach::class;

        protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

        protected static ?string $navigationGroup = 'Personal Development';

        protected static ?int $navigationSort = 1;

        /**
         * Построение формы редактирования коуча.
         * Form > 60 строк
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->description('Персональные данные коуча и его биография')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Полное имя')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Иван Иванов'),

                            Forms\Components\Select::make('user_id')
                                ->label('Связанный пользователь')
                                ->relationship('user', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\RichEditor::make('bio')
                                ->label('Биография / О себе')
                                ->required()
                                ->columnSpanFull()
                                ->placeholder('Расскажите о своем опыте и достижениях...'),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Специализация и Тарифы')
                        ->description('Направления работы и финансовые условия')
                        ->schema([
                            Forms\Components\TagsInput::make('specializations')
                                ->label('Направления (Темы)')
                                ->required()
                                ->placeholder('Тайм-менеджмент, Саморазвитие, Карьера')
                                ->default(['Self-improvement']),

                            Forms\Components\Group::make([
                                Forms\Components\TextInput::make('hourly_rate_kopecks')
                                    ->label('Почасовая ставка (в копейках)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(100)
                                    ->suffix('коп.')
                                    ->helperText('Например: 500000 для 5000 руб./час'),

                                Forms\Components\TextInput::make('rating')
                                    ->label('Текущий рейтинг')
                                    ->numeric()
                                    ->default(5.00)
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->columns(2),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Активен')
                                ->default(true)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-m-x-mark'),
                        ])
                        ->columns(1),

                    Forms\Components\Section::make('Системные данные')
                        ->description('Технические поля для аналитики')
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
                'index' => Pages\ListCoach::route('/'),
                'create' => Pages\CreateCoach::route('/create'),
                'edit' => Pages\EditCoach::route('/{record}/edit'),
                'view' => Pages\ViewCoach::route('/{record}'),
            ];
        }
}
