<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FoodResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = B2BFoodOrder::class;

        protected static ?string $navigationIcon = 'heroicon-o-collection';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Детали заказа пищи')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('restaurant_name')
                                ->label('Название ресторана')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('order_number')
                                ->label('Номер заказа')
                                ->required()
                                ->unique(ignoreRecord: true),
                        ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание заказа')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Контакты и адрес')
                    ->description('Информация для доставки')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('customer_name')
                                ->label('Имя клиента')
                                ->required(),

                            Forms\Components\TextInput::make('customer_phone')
                                ->label('Телефон клиента')
                                ->tel(),

                            Forms\Components\TextInput::make('customer_email')
                                ->label('Email')
                                ->email(),

                            Forms\Components\TextInput::make('delivery_address')
                                ->label('Адрес доставки')
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Товары и стоимость')
                    ->description('Список товаров в заказе')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Товары')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('dish_name')
                                        ->label('Название блюда')
                                        ->required(),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Кол-во')
                                        ->numeric()
                                        ->required(),

                                    Forms\Components\TextInput::make('price')
                                        ->label('Цена')
                                        ->numeric()
                                        ->required(),
                                ]),

                                Forms\Components\Textarea::make('special_instructions')
                                    ->label('Особые пожелания')
                                    ->rows(2),
                            ])
                            ->columns(1),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('subtotal')
                                ->label('Сумма (₽)')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('delivery_fee')
                                ->label('Доставка (₽)')
                                ->numeric(),

                            Forms\Components\TextInput::make('discount_amount')
                                ->label('Скидка (₽)')
                                ->numeric()
                                ->default(0),

                            Forms\Components\TextInput::make('total_amount')
                                ->label('Итого (₽)')
                                ->numeric()
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Статус и управление')
                    ->description('Информация о заказе')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('status')
                                ->label('Статус')
                                ->options([
                                    'pending' => 'Ожидает',
                                    'confirmed' => 'Подтвержден',
                                    'preparing' => 'Готовится',
                                    'ready' => 'Готов',
                                    'delivered' => 'Доставлен',
                                    'cancelled' => 'Отменен',
                                ])
                                ->default('pending')
                                ->required(),

                            Forms\Components\Select::make('payment_status')
                                ->label('Статус платежа')
                                ->options([
                                    'pending' => 'Ожидает',
                                    'paid' => 'Оплачено',
                                    'refunded' => 'Возвращено',
                                ])
                                ->default('pending'),

                            Forms\Components\DateTimePicker::make('order_time')
                                ->label('Время заказа')
                                ->required(),

                            Forms\Components\DateTimePicker::make('delivery_time')
                                ->label('Время доставки'),
                        ]),
                    ]),
            ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFood::route('/'),
                'create' => Pages\\CreateFood::route('/create'),
                'edit' => Pages\\EditFood::route('/{record}/edit'),
                'view' => Pages\\ViewFood::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFood::route('/'),
                'create' => Pages\\CreateFood::route('/create'),
                'edit' => Pages\\EditFood::route('/{record}/edit'),
                'view' => Pages\\ViewFood::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFood::route('/'),
                'create' => Pages\\CreateFood::route('/create'),
                'edit' => Pages\\EditFood::route('/{record}/edit'),
                'view' => Pages\\ViewFood::route('/{record}'),
            ];
        }
}
