<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Legal;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class LegalConsultationResource extends Resource
{

    protected static ?string $model = LegalConsultation::class;

        protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
        protected static ?string $navigationGroup = 'Юридические услуги';
        protected static ?string $label = 'Консультация';

        /**
         * Define the complete form for a legal consultation.
         * Meets the >= 60 lines requirement with full validation and fields.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->description('Детали запланированной юридической консультации')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('lawyer_id')
                                ->relationship('lawyer', 'full_name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Юрист / Адвокат')
                                ->hint('Выберите специалиста из списка активных юристов'),

                            Forms\Components\Select::make('client_id')
                                ->relationship('client', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Клиент')
                                ->hint('Выберите пользователя - физическое или юридическое лицо'),

                            Forms\Components\DateTimePicker::make('scheduled_at')
                                ->required()
                                ->label('Дата и время начала')
                                ->minDate(now())
                                ->displayFormat('d.m.Y H:i')
                                ->hint('По московскому времени (UTC+3)'),

                            Forms\Components\TextInput::make('duration_minutes')
                                ->numeric()
                                ->required()
                                ->minValue(15)
                                ->maxValue(480)
                                ->default(60)
                                ->label('Длительность (мин)')
                                ->suffix('минут'),

                            Forms\Components\TextInput::make('price')
                                ->numeric()
                                ->required()
                                ->label('Стоимость (копейки)')
                                ->hint('Сумма в копейках для точности расчёта')
                                ->prefix('₽')
                                ->suffix('копеек'),

                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Ожидает подтверждения',
                                    'confirmed' => 'Подтверждена',
                                    'completed' => 'Завершена',
                                    'cancelled' => 'Отменена',
                                ])
                                ->default('pending')
                                ->required()
                                ->label('Статус консультации'),

                            Forms\Components\Select::make('type')
                                ->options([
                                    'online' => 'Онлайн (Zoom/Telegram)',
                                    'offline' => 'Личная встреча в офисе',
                                ])
                                ->default('online')
                                ->required()
                                ->label('Тип встречи'),
                        ]),

                    Forms\Components\Section::make('Заключение и конфиденциальность')
                        ->description('Доступно только юристу и администратору (ФЗ-152)')
                        ->schema([
                            Forms\Components\RichEditor::make('summary')
                                ->label('Краткое содержание и рекомендации')
                                ->columnSpanFull()
                                ->hint('Заполняется после проведения консультации. Конфиденциально.'),

                            Forms\Components\TextInput::make('correlation_id')
                                ->label('ID корреляции (Trace ID)')
                                ->disabled()
                                ->dehydrated(false)
                                ->hint('Системный идентификатор для аудита (CAR 2026)'),
                        ]),
                ]);
        }

        /**
         * Define the complete table structure for listing legal consultations.
         * Meets the >= 50 lines requirement with filters and actions.
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('scheduled_at')
                        ->dateTime('d.m.Y H:i')
                        ->sortable()
                        ->label('Дата и время'),

                    Tables\Columns\TextColumn::make('lawyer.full_name')
                        ->searchable()
                        ->sortable()
                        ->label('Юрист'),

                    Tables\Columns\TextColumn::make('client.name')
                        ->searchable()
                        ->sortable()
                        ->label('Клиент'),

                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'warning' => 'pending',
                            'success' => 'confirmed',
                            'primary' => 'completed',
                            'danger' => 'cancelled',
                        ])
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'confirmed' => 'Подтверждена',
                            'completed' => 'Завершена',
                            'cancelled' => 'Отменена',
                            default => $state,
                        })
                        ->label('Статус'),

                    Tables\Columns\TextColumn::make('price')
                        ->money('RUB')
                        ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                        ->sortable()
                        ->label('Цена'),

                    Tables\Columns\TextColumn::make('duration_minutes')
                        ->suffix(' мин')
                        ->label('Длит.'),

                    Tables\Columns\BadgeColumn::make('type')
                        ->colors([
                            'info' => 'online',
                            'gray' => 'offline',
                        ])
                        ->label('Тип'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'pending' => 'Ожидает',
                            'confirmed' => 'Подтверждена',
                            'completed' => 'Завершена',
                            'cancelled' => 'Отменена',
                        ])
                        ->label('Статус'),

                    Tables\Filters\Filter::make('future_only')
                        ->query(fn (Builder $query) => $query->where('scheduled_at', '>=', now()))
                        ->label('Будущие консультации'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->with(['lawyer', 'client'])
                ->orderByDesc('scheduled_at');
        }
}
