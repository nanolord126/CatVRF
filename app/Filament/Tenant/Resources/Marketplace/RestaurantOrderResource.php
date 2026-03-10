<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\RestaurantOrderResource\Pages;
use App\Models\Tenants\RestaurantOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RestaurantOrderResource extends Resource
{
    protected static ?string $model = RestaurantOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = '🛒 Marketplace';
    protected static ?string $modelLabel = 'Заказ (Ресторан)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')->relationship('user', 'name')->required()->label('Клиент'),
                Forms\Components\TextInput::make('subtotal')->numeric()->required()->label('Сумма'),
                Forms\Components\TextInput::make('delivery_fee')->numeric()->label('Доставка'),
                Forms\Components\TextInput::make('delivery_address')->required()->label('Адрес'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Принят',
                        'cooking' => 'Готовится',
                        'delivering' => 'В пути',
                        'completed' => 'Доставлен',
                    ])->label('Статус'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('№'),
                Tables\Columns\TextColumn::make('user.name')->label('Клиент'),
                Tables\Columns\TextColumn::make('subtotal')->money('RUB')->label('Сумма'),
                Tables\Columns\BadgeColumn::make('status')->label('Статус'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantOrders::route('/'),
            'create' => Pages\CreateRestaurantOrder::route('/create'),
            'edit' => Pages\EditRestaurantOrder::route('/{record}/edit'),
        ];
    }
}
