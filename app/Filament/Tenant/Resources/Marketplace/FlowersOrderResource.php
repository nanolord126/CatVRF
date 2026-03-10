<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\FlowersOrderResource\Pages;
use App\Models\Tenants\FlowersOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlowersOrderResource extends Resource
{
    protected static ?string $model = FlowersOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = '🛒 Marketplace';
    protected static ?string $modelLabel = 'Заказ (Цветы)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')->relationship('user', 'name')->required()->label('Клиент'),
                Forms\Components\TextInput::make('total_amount')->numeric()->prefix('₽')->required()->label('Сумма'),
                Forms\Components\TextInput::make('delivery_fee')->numeric()->label('Доставка'),
                Forms\Components\TextInput::make('delivery_address')->required()->label('Адрес доставки'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Ожидание',
                        'preparing' => 'Подготовка',
                        'delivering' => 'Доставка',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен',
                    ])->required()->label('Статус'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('№'),
                Tables\Columns\TextColumn::make('user.name')->label('Клиент'),
                Tables\Columns\TextColumn::make('total_amount')->money('RUB')->label('Сумма'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'preparing',
                        'info' => 'delivering',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])->label('Статус'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Дата'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowersOrders::route('/'),
            'create' => Pages\CreateFlowersOrder::route('/create'),
            'edit' => Pages\EditFlowersOrder::route('/{record}/edit'),
        ];
    }
}
