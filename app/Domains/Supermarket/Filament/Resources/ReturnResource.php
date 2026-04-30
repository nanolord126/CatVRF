<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources;

use App\Domains\Supermarket\Enums\ReturnCondition;
use App\Domains\Supermarket\Enums\ReturnReason;
use App\Domains\Supermarket\Enums\ReturnStatus;
use App\Domains\Supermarket\Models\Return as ReturnModel;
use App\Domains\Supermarket\Models\ReturnItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * ReturnResource - Filament ресурс для управления возвратами.
 *
 * Предоставляет интерфейс для:
 * - Просмотра всех возвратов
 * - Одобрения/отклонения возвратов
 * - Фильтрации по статусу, приоритету, покупателю
 * - Просмотра деталей возврата с товарами
 */
class ReturnResource extends Resource
{
    protected static ?string $model = ReturnModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Возвраты';

    protected static ?string $modelLabel = 'Возврат';

    protected static ?string $pluralModelLabel = 'Возвраты';

    protected static ?string $navigationGroup = 'Supermarket';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'id')
                            ->label('Заказ')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('buyer_id')
                            ->relationship('buyer', 'name')
                            ->label('Покупатель')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('seller_id')
                            ->relationship('seller', 'name')
                            ->label('Продавец')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'В ожидании',
                                'approved' => 'Одобрен',
                                'rejected' => 'Отклонен',
                                'completed' => 'Завершен',
                                'refunded' => 'Деньги возвращены',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\Select::make('reason_type')
                            ->label('Причина возврата')
                            ->options([
                                'spoiled' => 'Испорчено',
                                'wrong_item' => 'Не тот товар',
                                'changed_mind' => 'Передумал',
                                'damaged' => 'Повреждено',
                                'expired' => 'Просрочено',
                                'other' => 'Другое',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('reason_comment')
                            ->label('Комментарий')
                            ->rows(3)
                            ->nullable(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Сумма возврата (коп.)')
                            ->numeric()
                            ->required()
                            ->default(0),

                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Фактическая сумма возврата (коп.)')
                            ->numeric()
                            ->required()
                            ->default(0),

                        Forms\Components\Toggle::make('is_cold_chain')
                            ->label('Холодная цепь')
                            ->default(false),

                        Forms\Components\Select::make('return_method')
                            ->label('Способ возврата')
                            ->options([
                                'pickup' => 'Самовывоз',
                                'courier' => 'Курьером',
                                'self_delivery' => 'Самостоятельно',
                            ])
                            ->required()
                            ->default('pickup'),

                        Forms\Components\Select::make('priority')
                            ->label('Приоритет')
                            ->options([
                                'normal' => 'Обычный',
                                'high' => 'Высокий',
                                'urgent' => 'Срочный',
                            ])
                            ->required()
                            ->default('normal'),

                        Forms\Components\TextInput::make('sub_vertical')
                            ->label('Под-вертикаль')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Фото доказательства')
                    ->schema([
                        Forms\Components\KeyValue::make('images')
                            ->label('Ссылки на фото')
                            ->keyLabel('URL')
                            ->valueLabel('Описание')
                            ->nullable(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Информация об обработке')
                    ->schema([
                        Forms\Components\Textarea::make('reject_reason')
                            ->label('Причина отказа')
                            ->rows(2)
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Дата одобрения')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Дата завершения')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('refunded_at')
                            ->label('Дата возврата денег')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_id')
                    ->label('Заказ')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('buyer.name')
                    ->label('Покупатель')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'primary' => 'completed',
                        'info' => 'refunded',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'В ожидании',
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонен',
                        'completed' => 'Завершен',
                        'refunded' => 'Деньги возвращены',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('reason_type')
                    ->label('Причина')
                    ->colors([
                        'danger' => 'spoiled',
                        'warning' => 'damaged',
                        'info' => 'wrong_item',
                        'gray' => 'changed_mind',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'spoiled' => 'Испорчено',
                        'wrong_item' => 'Не тот товар',
                        'changed_mind' => 'Передумал',
                        'damaged' => 'Повреждено',
                        'expired' => 'Просрочено',
                        'other' => 'Другое',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Приоритет')
                    ->colors([
                        'gray' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'normal' => 'Обычный',
                        'high' => 'Высокий',
                        'urgent' => 'Срочный',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_cold_chain')
                    ->label('Холодная цепь')
                    ->boolean()
                    ->trueIcon('heroicon-o-snowflake')
                    ->falseIcon('heroicon-o-x-mark'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В ожидании',
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонен',
                        'completed' => 'Завершен',
                        'refunded' => 'Деньги возвращены',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Приоритет')
                    ->options([
                        'normal' => 'Обычный',
                        'high' => 'Высокий',
                        'urgent' => 'Срочный',
                    ]),

                Tables\Filters\SelectFilter::make('reason_type')
                    ->label('Причина')
                    ->options([
                        'spoiled' => 'Испорчено',
                        'wrong_item' => 'Не тот товар',
                        'changed_mind' => 'Передумал',
                        'damaged' => 'Повреждено',
                        'expired' => 'Просрочено',
                        'other' => 'Другое',
                    ]),

                Tables\Filters\TernaryFilter::make('is_cold_chain')
                    ->label('Холодная цепь'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ReturnModel $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Одобрить возврат')
                    ->modalDescription('Вы уверены, что хотите одобрить этот возврат? Деньги будут возвращены на баланс покупателя.')
                    ->action(function (ReturnModel $record) {
                        $service = app(\App\Domains\Supermarket\Services\ReturnService::class);
                        $service->approve($record);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (ReturnModel $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина отказа')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ReturnModel $record, array $data) {
                        $service = app(\App\Domains\Supermarket\Services\ReturnService::class);
                        $service->reject($record, $data['reason']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc')
            ->defaultSort('created_at', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            'items' => Tables\RelationManagers\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturns::route('/'),
            'create' => Pages\CreateReturn::route('/create'),
            'view' => Pages\ViewReturn::route('/{record}'),
            'edit' => Pages\EditReturn::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
