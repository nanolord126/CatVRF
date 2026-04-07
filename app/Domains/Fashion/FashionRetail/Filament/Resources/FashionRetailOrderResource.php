<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Filament\Resources;

use Filament\Resources\Resource;

final class FashionRetailOrderResource extends Resource
{

    protected static ?string $model = FashionRetailOrder::class;

        protected static ?string $navigationGroup = 'Fashion Retail';

        protected static ?string $navigationLabel = 'Orders';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Order Info')->schema([
                    TextInput::make('order_number')->disabled()->unique(),
                    Select::make('shop_id')->relationship('shop', 'name')->required(),
                    Select::make('user_id')->relationship('user', 'name')->required(),
                    Select::make('status')->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])->required(),
                ])->columns(2),

                Section::make('Amounts')->schema([
                    TextInput::make('total_amount')->numeric()->step(0.01)->required(),
                    TextInput::make('discount_amount')->numeric()->step(0.01)->default(0),
                    TextInput::make('commission_amount')->numeric()->step(0.01)->default(0),
                    TextInput::make('delivery_fee')->numeric()->step(0.01)->default(0),
                ])->columns(2),

                Section::make('Delivery')->schema([
                    TextInput::make('delivery_address')->required(),
                    Select::make('delivery_method')->options([
                        'standard' => 'Standard',
                        'express' => 'Express',
                        'pickup' => 'Pickup',
                    ])->default('standard'),
                    TextInput::make('tracking_number'),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('order_number')->searchable().sortable(),
                TextColumn::make('shop.name')->searchable(),
                TextColumn::make('user.name')->searchable(),
                TextColumn::make('total_amount')->numeric()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}
