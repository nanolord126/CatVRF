<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Filament\Resources\SupermarketOrderResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class SupermarketOrderResource extends Resource
{
    protected static ?string $model = SupermarketOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Заказы Supermarket';

    protected static ?string $modelLabel = 'Заказ';

    protected static ?string $pluralModelLabel = 'Заказы';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationGroup = 'Supermarket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->maxLength(255),

                        Forms\Components\Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->label('Тенант'),

                        Forms\Components\TextInput::make('user_id')
                            ->label('ID пользователя')
                            ->numeric(),

                        Forms\Components\Select::make('sub_vertical')
                            ->options([
                                'meat_shops' => 'Мясные магазины',
                                'farm_direct' => 'Фермерские продукты',
                                'vegan_products' => 'Веганские продукты',
                                'confectionery' => 'Кондитерские изделия',
                                'grocery_and_delivery' => 'Бакалея и доставка',
                                'food' => 'Еда',
                                'office_catering' => 'Офисный кейтеринг',
                            ])
                            ->label('Под-вертикаль'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Ожидает',
                                'paid' => 'Оплачен',
                                'processing' => 'В обработке',
                                'shipped' => 'Отправлен',
                                'delivered' => 'Доставлен',
                                'cancelled' => 'Отменён',
                            ])
                            ->required()
                            ->label('Статус'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Детали заказа')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Сумма заказа')
                            ->numeric()
                            ->suffix('₽'),

                        Forms\Components\TextInput::make('delivery_cost')
                            ->label('Стоимость доставки')
                            ->numeric()
                            ->suffix('₽'),

                        Forms\Components\TextInput::make('delivery_eta')
                            ->label('Время доставки (мин)')
                            ->numeric(),

                        Forms\Components\Textarea::make('delivery_address')
                            ->label('Адрес доставки')
                            ->rows(2),

                        Forms\Components\TextInput::make('delivery_slot')
                            ->label('Слот доставки'),

                        Forms\Components\Toggle::make('cold_chain_required')
                            ->label('Требуется холодовая цепь'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\TextInput::make('payment_id')
                            ->label('ID платежа'),

                        Forms\Components\TextInput::make('correlation_id')
                            ->label('Correlation ID')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Дата отмены'),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Создан')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('updated_at')
                            ->label('Обновлён')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->limit(10)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user_id')
                    ->label('Пользователь')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sub_vertical')
                    ->label('Под-вертикаль')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'meat_shops' => 'danger',
                        'farm_direct' => 'success',
                        'vegan_products' => 'warning',
                        'confectionery' => 'info',
                        'grocery_and_delivery' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('delivery_cost')
                    ->label('Доставка')
                    ->money('RUB')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('cold_chain_required')
                    ->label('Холодовая цепь')
                    ->boolean()
                    ->trueIcon('heroicon-o-snowflake')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидает',
                        'paid' => 'Оплачен',
                        'processing' => 'В обработке',
                        'shipped' => 'Отправлен',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                    ])
                    ->label('Статус'),

                Tables\Filters\SelectFilter::make('sub_vertical')
                    ->options([
                        'meat_shops' => 'Мясные магазины',
                        'farm_direct' => 'Фермерские продукты',
                        'vegan_products' => 'Веганские продукты',
                        'confectionery' => 'Кондитерские изделия',
                        'grocery_and_delivery' => 'Бакалея и доставка',
                        'food' => 'Еда',
                        'office_catering' => 'Офисный кейтеринг',
                    ])
                    ->label('Под-вертикаль'),

                Tables\Filters\TernaryFilter::make('cold_chain_required')
                    ->label('Холодовая цепь'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('По'),
                    ])
                    ->query(function ($query, array $data): void {
                        $query->when(
                            $data['created_from'],
                            fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                        )->when(
                            $data['created_until'],
                            fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                        );
                    })
                    ->label('Дата создания'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('accept')
                    ->label('Принять')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SupermarketOrder $record): bool => $record->status === 'pending')
                    ->action(function (SupermarketOrder $record): void {
                        $record->update(['status' => 'processing']);
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (SupermarketOrder $record): bool => in_array($record->status, ['pending', 'processing']))
                    ->action(function (SupermarketOrder $record): void {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('ready_for_delivery')
                    ->label('Готов к доставке')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (SupermarketOrder $record): bool => $record->status === 'processing')
                    ->action(function (SupermarketOrder $record): void {
                        $record->update(['status' => 'shipped']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'tenant',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupermarketOrders::route('/'),
            'create' => Pages\CreateSupermarketOrder::route('/create'),
            'view' => Pages\ViewSupermarketOrder::route('/{record}'),
            'edit' => Pages\EditSupermarketOrder::route('/{record}/edit'),
        ];
    }
}
