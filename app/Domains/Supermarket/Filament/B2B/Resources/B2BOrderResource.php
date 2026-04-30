<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\B2B\Resources;

use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Domains\Supermarket\Models\B2BCompany;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class B2BOrderResource extends Resource
{
    protected static ?string $model = SupermarketOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Мои заказы';

    protected static ?string $modelLabel = 'Заказ';

    protected static ?string $pluralModelLabel = 'Заказы';

    protected static ?string $navigationGroup = 'Мои заказы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о заказе')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Ожидает оплаты',
                                'paid' => 'Оплачен',
                                'processing' => 'В обработке',
                                'shipped' => 'Отправлен',
                                'delivered' => 'Доставлен',
                                'cancelled' => 'Отменён',
                            ])
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Сумма заказа')
                            ->prefix('₽')
                            ->disabled()
                            ->numeric(),
                        Forms\Components\TextInput::make('delivery_cost')
                            ->label('Стоимость доставки')
                            ->prefix('₽')
                            ->disabled()
                            ->numeric(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Адрес доставки')
                    ->schema([
                        Forms\Components\Textarea::make('delivery_address')
                            ->label('Адрес')
                            ->disabled()
                            ->rows(3),
                        Forms\Components\Textarea::make('delivery_slot')
                            ->label('Слот доставки')
                            ->disabled()
                            ->rows(2),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Товары в заказе')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\TextInput::make('product_name')
                                    ->label('Товар')
                                    ->disabled(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Количество')
                                    ->disabled()
                                    ->numeric(),
                                Forms\Components\TextInput::make('price')
                                    ->label('Цена')
                                    ->prefix('₽')
                                    ->disabled()
                                    ->numeric(),
                            ])
                            ->columns(3)
                            ->disabled()
                            ->defaultItems(0),
                    ])
                    ->columns(1),
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
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'info' => 'processing',
                        'primary' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_cost')
                    ->label('Доставка')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\IconColumn::make('cold_chain_required')
                    ->label('Холодовая цепь')
                    ->boolean()
                    ->trueIcon('heroicon-o-snowflake')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидает оплаты',
                        'paid' => 'Оплачен',
                        'processing' => 'В обработке',
                        'shipped' => 'Отправлен',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                    ]),
                Tables\Filters\TernaryFilter::make('cold_chain_required')
                    ->label('Требует холодовой цепи'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download_invoice')
                    ->label('Счёт')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('supermarket.b2b.documents.invoice', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('download_upd')
                    ->label('УПД')
                    ->icon('heroicon-o-document')
                    ->url(fn ($record) => route('supermarket.b2b.documents.upd', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // No bulk actions for orders
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->where('is_b2b', true);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
            'view' => \Filament\Resources\Pages\ViewRecord::route('/{record}'),
        ];
    }
}
