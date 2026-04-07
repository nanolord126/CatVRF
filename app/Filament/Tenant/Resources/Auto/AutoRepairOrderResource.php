<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Resource;

final class AutoRepairOrderResource extends Resource
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static ?string $model = AutoRepairOrder::class;

        protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

        protected static ?string $navigationGroup = 'Автосервис';

        protected static ?string $label = 'Заказ-наряд';

        protected static ?string $pluralLabel = 'Заказ-наряды';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Основная информация')
                    ->icon('heroicon-m-ticket')
                    ->description('Данные заказ-наряда')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('order_number')
                            ->label('Номер наряда')
                            ->disabled()
                            ->columnSpan(2),

                        Forms\Components\Select::make('auto_vehicle_id')
                            ->label('Автомобиль')
                            ->relationship('vehicle', 'vin')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (AutoVehicle $record) => "{$record->brand} {$record->model} ({$record->vin})")
                            ->columnSpan(2),

                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                    ])->columns(4),

                Forms\Components\Section::make('Статус и сроки')
                    ->icon('heroicon-m-clock')
                    ->description('Данные работы')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидание',
                                'in_progress' => 'В работе',
                                'completed' => 'Завершен',
                                'cancelled' => 'Отменен',
                            ])
                            ->required()
                            ->default('pending')
                            ->columnSpan(2),

                        Forms\Components\DateTimePicker::make('planned_at')
                            ->label('Плановая дата')
                            ->required()
                            ->native(false)
                            ->columnSpan(2),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Дата начала работ')
                            ->native(false)
                            ->disabled()
                            ->columnSpan(2),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Дата завершения')
                            ->native(false)
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),

                Forms\Components\Section::make('Диагностика и жалобы')
                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                    ->description('Проблемы и выявленные неисправности')
                    ->schema([
                        Forms\Components\Textarea::make('client_complaint')
                            ->label('Жалоба клиента')
                            ->maxLength(1000)
                            ->columnSpan('full')
                            ->required(),

                        Forms\Components\Textarea::make('mechanic_report')
                            ->label('Отчет мастера')
                            ->maxLength(2000)
                            ->columnSpan('full')
                            ->helperText('Описание выявленных проблем и проведённых работ'),

                        Forms\Components\TagsInput::make('work_performed')
                            ->label('Выполненные работы')
                            ->columnSpan('full'),

                        Forms\Components\FileUpload::make('diagnostic_photos')
                            ->label('Фото диагностики')
                            ->multiple()
                            ->directory('repair-diagnostics')
                            ->columnSpan('full'),
                    ])->columnSpan('full'),

                Forms\Components\Section::make('Смета и расчёты')
                    ->icon('heroicon-m-banknote')
                    ->description('Стоимость работ и запчастей')
                    ->schema([
                        Forms\Components\TextInput::make('labor_cost_kopecks')
                            ->label('Стоимость работ (коп)')
                            ->numeric()
                            ->default(0)
                            ->suffix('₽')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('parts_cost_kopecks')
                            ->label('Стоимость запчастей (коп)')
                            ->numeric()
                            ->default(0)
                            ->suffix('₽')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('total_cost_kopecks')
                            ->label('Итого (коп)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('₽')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Скидка (%)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('final_price_kopecks')
                            ->label('Финальная цена (коп)')
                            ->numeric()
                            ->disabled()
                            ->suffix('₽')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('warranty_months')
                            ->label('Гарантия (месяцев)')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(2),
                    ])->columns(4),

                Forms\Components\Section::make('Расходные материалы')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->description('Использованные запчасти')
                    ->schema([
                        Forms\Components\Repeater::make('parts')
                            ->label('Запчасти')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('auto_part_id')
                                    ->label('Запчасть')
                                    ->relationship('part', 'name')
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Кол-во')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit_price_kopecks')
                                    ->label('Цена/шт (коп)')
                                    ->numeric()
                                    ->disabled()
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->columnSpan('full'),
                    ])->columnSpan('full'),

                Forms\Components\Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn () => tenant('id')),

                        Forms\Components\Hidden::make('correlation_id')
                            ->default(fn () => Str::uuid()),

                        Forms\Components\Hidden::make('business_group_id')
                            ->default(fn () => filament()->getTenant()?->active_business_group_id),

                        Forms\Components\TextInput::make('created_at')
                            ->label('Создан')
                            ->disabled()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('updated_at')
                            ->label('Обновлён')
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('uuid')
                        ->label('UUID')
                        ->copyable()
                        ->hidden()
                        ->searchable(),

                    Tables\Columns\TextColumn::make('order_number')
                        ->label('Номер наряда')
                        ->searchable()
                        ->sortable()
                        ->icon('heroicon-m-ticket'),

                    Tables\Columns\TextColumn::make('vehicle.vin')
                        ->label('VIN')
                        ->searchable()
                        ->limit(20)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('vehicle.brand')
                        ->label('Марка/Модель')
                        ->getStateUsing(fn ($record) => "{$record->vehicle?->brand} {$record->vehicle?->model}")
                        ->searchable()
                        ->limit(30),

                    Tables\Columns\TextColumn::make('client.name')
                        ->label('Клиент')
                        ->searchable()
                        ->sortable()
                        ->limit(30),

                    Tables\Columns\BadgeColumn::make('status')
                        ->label('Статус')
                        ->colors([
                            'gray' => 'pending',
                            'warning' => 'in_progress',
                            'success' => 'completed',
                            'danger' => 'cancelled',
                        ])
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'in_progress' => 'В работе',
                            'completed' => 'Завершен',
                            'cancelled' => 'Отменен',
                            default => $state,
                        })
                        ->sortable(),

                    Tables\Columns\TextColumn::make('labor_cost_kopecks')
                        ->label('Стоимость работ')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('parts_cost_kopecks')
                        ->label('Запчасти')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('total_cost_kopecks')
                        ->label('Итого')
                        ->money('RUB', divideBy: 100)
                        ->sortable()
                        ->weight('bold'),

                    Tables\Columns\TextColumn::make('warranty_months')
                        ->label('Гарантия')
                        ->formatStateUsing(fn ($state) => $state ? "{$state} мес" : 'нет')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('planned_at')
                        ->label('Плановая дата')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Создан')
                        ->dateTime('d.m.Y H:i')
                        ->sortable()
                        ->hidden(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'Ожидание',
                            'in_progress' => 'В работе',
                            'completed' => 'Завершен',
                            'cancelled' => 'Отменен',
                        ])
                        ->multiple()
                        ->preload()
                        ->searchable(),

                    Tables\Filters\SelectFilter::make('vehicle.brand')
                        ->label('Марка')
                        ->relationship('vehicle', 'brand')
                        ->preload()
                        ->searchable()
                        ->distinct(),

                    Tables\Filters\Filter::make('high_cost')
                        ->label('Стоимость > 50 000 ₽')
                        ->query(fn (Builder $query) => $query->where('total_cost_kopecks', '>', 5000000)),

                    Tables\Filters\TernaryFilter::make('completed')
                        ->label('Завершённые')
                        ->queries(
                            true: fn (Builder $query) => $query->where('status', 'completed'),
                            false: fn (Builder $query) => $query->where('status', '!=', 'completed')
                        ),

                    Tables\Filters\TrashedFilter::make(),
                ])
                ->actions([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make()
                            ->after(function () {
                                $this->logger->info('AutoRepairOrder deleted', [
                                    'resource' => 'AutoRepairOrder',
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => Str::uuid(),
                                ]);
                            }),
                        Tables\Actions\RestoreAction::make()
                            ->after(function () {
                                $this->logger->info('AutoRepairOrder restored', [
                                    'resource' => 'AutoRepairOrder',
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => Str::uuid(),
                                ]);
                            }),
                        Tables\Actions\Action::make('complete')
                            ->label('Завершить')
                            ->icon('heroicon-m-check-circle')
                            ->color('success')
                            ->visible(fn (Model $record) => $record->status !== 'completed' && $record->status !== 'cancelled')
                            ->action(function (Model $record) {
                                $record->update(['status' => 'completed', 'completed_at' => now()]);
                                $this->logger->info('AutoRepairOrder completed', [
                                    'resource' => 'AutoRepairOrder',
                                    'resource_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            }),
                        Tables\Actions\Action::make('cancel')
                            ->label('Отменить')
                            ->icon('heroicon-m-x-circle')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->visible(fn (Model $record) => !in_array($record->status, ['completed', 'cancelled']))
                            ->action(function (Model $record) {
                                $record->update(['status' => 'cancelled']);
                                $this->logger->info('AutoRepairOrder cancelled', [
                                    'resource' => 'AutoRepairOrder',
                                    'resource_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            }),
                    ]),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make()
                            ->after(function () {
                                $this->logger->info('AutoRepairOrders bulk deleted', [
                                    'resource' => 'AutoRepairOrder',
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => Str::uuid(),
                                ]);
                            }),
                        Tables\Actions\BulkAction::make('mark_completed')
                            ->label('Отметить завершёнными')
                            ->icon('heroicon-m-check')
                            ->deselectRecordsAfterCompletion()
                            ->action(function (Collection $records) {
                                foreach ($records as $record) {
                                    $record->update(['status' => 'completed', 'completed_at' => now()]);
                                    $this->logger->info('AutoRepairOrder bulk completed', [
                                        'resource' => 'AutoRepairOrder',
                                        'resource_id' => $record->id,
                                        'user_id' => $this->guard->id(),
                                        'correlation_id' => $record->correlation_id,
                                    ]);
                                }
                            }),
                        Tables\Actions\BulkAction::make('mark_cancelled')
                            ->label('Отменить')
                            ->icon('heroicon-m-x-mark')
                            ->deselectRecordsAfterCompletion()
                            ->requiresConfirmation()
                            ->action(function (Collection $records) {
                                foreach ($records as $record) {
                                    $record->update(['status' => 'cancelled']);
                                    $this->logger->info('AutoRepairOrder bulk cancelled', [
                                        'resource' => 'AutoRepairOrder',
                                        'resource_id' => $record->id,
                                        'user_id' => $this->guard->id(),
                                        'correlation_id' => $record->correlation_id,
                                    ]);
                                }
                            }),
                    ]),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListAutoRepairOrders::route('/'),
                'create' => Pages\CreateAutoRepairOrder::route('/create'),
                'edit' => Pages\EditAutoRepairOrder::route('/{record}/edit'),
            ];
        }
}
