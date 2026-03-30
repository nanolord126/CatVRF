<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketSaleResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = TicketSale::class;
        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
        protected static ?string $navigationLabel = 'Продажи билетов';
        protected static ?int $navigationSort = 4;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Информация о продаже')
                        ->schema([
                            Forms\Components\Select::make('event_id')
                                ->label('Событие')
                                ->relationship('event', 'title')
                                ->required(),
                            Forms\Components\Select::make('buyer_id')
                                ->label('Покупатель')
                                ->relationship('buyer', 'email')
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Количество')
                                ->numeric()
                                ->required()
                                ->disabled(),
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Цена за единицу')
                                ->numeric()
                                ->disabled(),
                            Forms\Components\TextInput::make('subtotal')
                                ->label('Сумма')
                                ->numeric()
                                ->disabled(),
                            Forms\Components\TextInput::make('commission_amount')
                                ->label('Комиссия (14%)')
                                ->numeric()
                                ->disabled(),
                            Forms\Components\TextInput::make('total_amount')
                                ->label('Итого')
                                ->numeric()
                                ->disabled(),
                        ]),

                    Forms\Components\Section::make('Статусы')
                        ->schema([
                            Forms\Components\Select::make('payment_status')
                                ->label('Статус платежа')
                                ->options([
                                    'pending' => 'В ожидании',
                                    'paid' => 'Оплачено',
                                    'failed' => 'Не удалось',
                                ])
                                ->disabled(),
                            Forms\Components\Select::make('sale_status')
                                ->label('Статус продажи')
                                ->options([
                                    'active' => 'Активно',
                                    'refunded' => 'Возвращено',
                                    'cancelled' => 'Отменено',
                                ])
                                ->disabled(),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('transaction_id')
                        ->label('Транзакция')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('event.title')
                        ->label('Событие')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('buyer.email')
                        ->label('Покупатель')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('quantity')
                        ->label('Билетов'),
                    Tables\Columns\TextColumn::make('total_amount')
                        ->label('Сумма')
                        ->money('RUB'),
                    Tables\Columns\TextColumn::make('payment_status')
                        ->label('Платеж')
                        ->badge()
                        ->color(fn($state) => match($state) {
                            'paid' => 'success',
                            'pending' => 'warning',
                            'failed' => 'danger',
                            default => 'gray',
                        }),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Дата')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('payment_status')
                        ->label('Статус платежа')
                        ->options([
                            'pending' => 'В ожидании',
                            'paid' => 'Оплачено',
                            'failed' => 'Не удалось',
                        ]),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
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
                'index' => Pages\ListTicketSales::route('/'),
                'view' => Pages\ViewTicketSale::route('/{record}'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant('id'))
                ->with(['event', 'buyer', 'organizer']);
        }
}
