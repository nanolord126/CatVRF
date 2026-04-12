<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class EnrollmentResource extends Resource
{

    protected static ?string $model = Enrollment::class;

        protected static ?string $navigationIcon = 'heroicon-o-users';

        protected static ?string $navigationGroup = 'Personal Development';

        protected static ?int $navigationSort = 3;

        /**
         * Построение формы регистрации на программу.
         * Form > 60 строк
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Детали участия')
                        ->description('Выбранная программа и данные студента')
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->label('Студент (Пользователь)')
                                ->relationship('user', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\Select::make('program_id')
                                ->label('Программа обучения')
                                ->relationship('program', 'title')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\Group::make([
                                Forms\Components\Select::make('status')
                                    ->label('Статус участия')
                                    ->options([
                                        'pending' => 'Ожидание оплаты',
                                        'active' => 'Обучается (Активен)',
                                        'completed' => 'Курс завершён',
                                        'cancelled' => 'Отменено участником',
                                        'failed' => 'Отчислено/Ошибка',
                                    ])
                                    ->required()
                                    ->default('pending'),

                                Forms\Components\TextInput::make('progress_percent')
                                    ->label('Прогресс обучения (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->helperText('Рассчитывается автоматически на основе вех (Milestones).'),
                            ])
                            ->columns(2),

                            Forms\Components\Group::make([
                                Forms\Components\DatePicker::make('enrolled_at')
                                    ->label('Дата регистрации')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\DatePicker::make('completed_at')
                                    ->label('Дата завершения')
                                    ->nullable(),
                            ])
                            ->columns(2),
                        ])
                        ->columns(1),

                    Forms\Components\Section::make('Режим доступа')
                        ->description('Настройка B2B или B2C режима')
                        ->schema([
                            Forms\Components\Toggle::make('mode_is_b2b')
                                ->label('B2B Режим (Корпоративное обучение)')
                                ->default(false)
                                ->onIcon('heroicon-m-briefcase')
                                ->offIcon('heroicon-m-user')
                                ->reactive()
                                ->helperText('Если активно, оплата списывается с бизнес-группы через B2C/B2B шлюз.'),

                            Forms\Components\Select::make('business_group_id')
                                ->label('Бизнес-группа (ИНН)')
                                ->relationship('businessGroup', 'name')
                                ->searchable()
                                ->preload()
                                ->visible(fn (Forms\Get $get) => (bool) $get('mode_is_b2b'))
                                ->required(fn (Forms\Get $get) => (bool) $get('mode_is_b2b')),

                            Forms\Components\TextInput::make('paid_amount_kopecks')
                                ->label('Фактически оплачено (в копейках)')
                                ->numeric()
                                ->default(0)
                                ->suffix('коп.')
                                ->helperText('Сумма, списанная с кошелька пользователя/бизнеса.'),
                        ])
                        ->columns(1),

                    Forms\Components\Section::make('Системные / Мета')
                        ->description('Технические данные транзакции')
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
                'index' => Pages\ListEnrollment::route('/'),
                'create' => Pages\CreateEnrollment::route('/create'),
                'edit' => Pages\EditEnrollment::route('/{record}/edit'),
                'view' => Pages\ViewEnrollment::route('/{record}'),
            ];
        }
}
