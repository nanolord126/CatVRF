<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Filament\Notifications\Notification;

final class FreelanceOrderResource extends Resource
{

    protected static ?string $model = FreelanceOrder::class;

        protected static ?string $navigationIcon = 'heroicon-o-document-currency-rupee';

        protected static ?string $navigationGroup = 'Фриланс Биржа';

        protected static ?string $label = 'Заказ';

        protected static ?string $pluralLabel = 'Заказы';

        /**
         * Форма управления заказом (>60 строк)
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Параметры сделки')
                        ->columns(2)
                        ->schema([
                            TextInput::make('title')
                                ->label('Заголовок заказа')
                                ->required()
                                ->columnSpanFull(),

                            Select::make('client_id')
                                ->label('Клиент (Заказчик)')
                                ->relationship('client', 'name')
                                ->searchable()
                                ->required()
                                ->disabledOn('edit'),

                            Select::make('freelancer_id')
                                ->label('Исполнитель (Фрилансер)')
                                ->relationship('freelancer', 'full_name')
                                ->searchable()
                                ->required()
                                ->disabledOn('edit'),

                            TextInput::make('budget_kopecks')
                                ->label('Бюджет заказа (коп.)')
                                ->numeric()
                                ->required()
                                ->prefix('₽')
                                ->helperText('Сумма в копейках. Комиссия 14% рассчитывается при сохранении.'),

                            TextInput::make('deadline_at')
                                ->label('Крайний срок (Deadline)')
                                ->type('datetime-local')
                                ->required(),

                            Select::make('status')
                                ->label('Текущий статус')
                                ->options([
                                    'pending' => 'Ожидание (Черновик)',
                                    'escrow_hold' => 'Средства на Эскроу',
                                    'in_progress' => 'В работе',
                                    'completed' => 'Завершен (Выплачено)',
                                    'disputed' => 'Спор / Арбитраж',
                                    'cancelled' => 'Отменен',
                                ])
                                ->required()
                                ->disabled() // Статусы меняются только через экшены (Канон)
                                ->columnSpanFull(),
                        ]),

                    Section::make('Техническое задание')
                        ->schema([
                            RichEditor::make('requirements')
                                ->label('ТЗ / Требования к результату')
                                ->required()
                                ->columnSpanFull(),
                        ]),

                    Section::make('Эскроу-контракт')
                        ->relationship('contract')
                        ->collapsed() // Показываем данные контракта
                        ->schema([
                            TextInput::make('contract_number')
                                ->label('Номер счета Безопасной сделки')
                                ->disabled(),

                            TextInput::make('escrow_status')
                                ->label('Статус холда')
                                ->badge()
                                ->disabled(),

                            TextInput::make('escrow_amount_kopecks')
                                ->label('Сумма в холде')
                                ->disabled(),

                            RichEditor::make('arbitration_comment')
                                ->label('Решение арбитража')
                                ->disabled()
                                ->columnSpanFull(),
                        ]),
                ]);
        }

        /**
         * Таблица заказов с кастомными экшенами для Эскроу (Канон 2026)
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('title')
                        ->label('Заказ')
                        ->searchable()
                        ->sortable()
                        ->limit(40),

                    TextColumn::make('client.name')
                        ->label('Заказчик')
                        ->searchable(),

                    TextColumn::make('freelancer.full_name')
                        ->label('Исполнитель'),

                    TextColumn::make('budget_kopecks')
                        ->label('Бюджет')
                        ->money('RUB', divisor: 100)
                        ->sortable(),

                    TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'escrow_hold' => 'info',
                            'in_progress' => 'warning',
                            'completed' => 'success',
                            'disputed' => 'danger',
                            default => 'gray',
                        }),

                    TextColumn::make('deadline_at')
                        ->label('Дедлайн')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'in_progress' => 'В работе',
                            'escrow_hold' => 'Оплачено (Escrow)',
                            'completed' => 'Завершено',
                        ]),
                ])
                ->actions([
                    // Кнопка 1: Холдирование средств (для клиента)
                    Action::make('hold_funds')
                        ->label('Оплатить (Hold)')
                        ->icon('heroicon-o-lock-closed')
                        ->visible(fn (FreelanceOrder $record) => $record->status === 'pending')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (FreelanceOrder $record, ContractService $service) {
                             $service->holdFunds($record->contract->id);
                             Notification::make()->title('Средства успешно заморожены на Эскроу')->success()->send();
                        }),

                    // Кнопка 2: Принять работу и выплатить (для клиента)
                    Action::make('complete_payout')
                        ->label('Принять работу')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (FreelanceOrder $record) => $record->status === 'in_progress')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (FreelanceOrder $record, FreelanceService $service) {
                             $service->completeOrder($record->id);
                             Notification::make()->title('Работа принята, гонорар выплачен')->success()->send();
                        }),

                    // Кнопка 3: Открыть спор (для обеих сторон)
                    Action::make('open_dispute')
                        ->label('Спор / Арбитраж')
                        ->icon('heroicon-o-scale')
                        ->visible(fn (FreelanceOrder $record) => in_array($record->status, ['in_progress', 'escrow_hold']))
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Суть претензии')
                                ->required(),
                        ])
                        ->action(function (FreelanceOrder $record, array $data) {
                            $record->update(['status' => 'disputed']);
                            Notification::make()->title('Арбитраж CAT-VRF уведомлен о споре')->warning()->send();
                        }),

                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFreelanceOrders::route('/'),
                'create' => Pages\CreateFreelanceOrder::route('/create'),
                'view' => Pages\ViewFreelanceOrder::route('/{record}'),
                'edit' => Pages\EditFreelanceOrder::route('/{record}/edit'),
            ];
        }
}
