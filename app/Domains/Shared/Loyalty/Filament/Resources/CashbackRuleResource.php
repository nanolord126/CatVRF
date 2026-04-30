<?php

declare(strict_types=1);

namespace App\Domains\Shared\Loyalty\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use App\Domains\Shared\Loyalty\Models\CashbackRule;

final class CashbackRuleResource extends Resource
{
    protected static ?string $model = CashbackRule::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Модерация';
    protected static ?string $navigationLabel = 'Правила кэшбека';
    protected static ?int $navigationSort = 1;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Продавец')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('percent')
                            ->label('Процент кэшбека')
                            ->numeric()
                            ->suffix('%')
                            ->required()
                            ->minValue(0.1)
                            ->maxValue(50),

                        Forms\Components\TextInput::make('min_order_amount')
                            ->label('Минимальная сумма заказа')
                            ->numeric()
                            ->prefix('₽')
                            ->default(1000),

                        Forms\Components\TextInput::make('max_cashback_amount')
                            ->label('Макс. сумма кэшбека за заказ')
                            ->numeric()
                            ->prefix('₽')
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Условия')
                    ->schema([
                        Forms\Components\Select::make('sub_vertical')
                            ->label('Подвертикаль')
                            ->options([
                                'meat_shops' => 'Мясные лавки',
                                'vegan_products' => 'Веган',
                                'confectionery' => 'Кондитерка',
                                'farm_direct' => 'Фермерские',
                                'grocery_and_delivery' => 'Бакалея и доставка',
                                'food' => 'Готовая еда',
                            ])
                            ->nullable(),

                        Forms\Components\KeyValue::make('conditions')
                            ->label('Дополнительные условия')
                            ->keyLabel('Параметр')
                            ->valueLabel('Значение')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Статус модерации')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активно после одобрения')
                            ->default(true),

                        Forms\Components\Select::make('moderation_status')
                            ->label('Статус модерации')
                            ->options([
                                'pending' => 'На проверке',
                                'approved' => 'Одобрено',
                                'rejected' => 'Отклонено',
                            ])
                            ->default('pending')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Продавец')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('percent')
                    ->label('Кэшбек')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_vertical')
                    ->label('Подвертикаль')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'meat_shops' => 'Мясные лавки',
                        'vegan_products' => 'Веган',
                        'confectionery' => 'Кондитерка',
                        'farm_direct' => 'Фермерские',
                        'grocery_and_delivery' => 'Бакалея и доставка',
                        'food' => 'Готовая еда',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('min_order_amount')
                    ->label('Мин. сумма')
                    ->money('RUB'),

                Tables\Columns\TextColumn::make('max_cashback_amount')
                    ->label('Макс. кэшбек')
                    ->money('RUB')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('moderation_status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'На проверке',
                        'approved' => 'Одобрено',
                        'rejected' => 'Отклонено',
                        default => $state,
                    }),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Активно'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('moderation_status')
                    ->label('Статус модерации')
                    ->options([
                        'pending' => 'На проверке',
                        'approved' => 'Одобрено',
                        'rejected' => 'Отклонено',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активно'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (CashbackRule $record) => $record->update([
                        'moderation_status' => 'approved',
                        'is_active' => true
                    ]))
                    ->visible(fn ($record) => $record->moderation_status === 'pending'),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (CashbackRule $record) => $record->update([
                        'moderation_status' => 'rejected',
                        'is_active' => false
                    ]))
                    ->visible(fn ($record) => $record->moderation_status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Shared\Loyalty\Filament\Resources\CashbackRuleResource\Pages\ListCashbackRules::route('/'),
            'create' => \App\Domains\Shared\Loyalty\Filament\Resources\CashbackRuleResource\Pages\CreateCashbackRule::route('/create'),
            'edit' => \App\Domains\Shared\Loyalty\Filament\Resources\CashbackRuleResource\Pages\EditCashbackRule::route('/{record}/edit'),
        ];
    }
}
