<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers;

use App\Domains\Flowers\Models\FlowerOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class FlowerOrderResource extends Resource
{
    protected static ?string $model = FlowerOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Flowers';
    protected static ?string $navigationLabel = 'Orders';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->required(),
            Forms\Components\Select::make('bouquet_id')
                ->relationship('bouquet', 'name'),
            Forms\Components\Select::make('perfume_id')
                ->relationship('perfume', 'name'),
            Forms\Components\TextInput::make('quantity')
                ->required()
                ->numeric()
                ->default(1),
            Forms\Components\TextInput::make('total_price')
                ->required()
                ->numeric()
                ->prefix('₽'),
            Forms\Components\Textarea::make('delivery_address')
                ->required(),
            Forms\Components\DateTimePicker::make('delivery_at'),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')->searchable(),
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('bouquet.name'),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('RUB', divideBy: 100),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('delivery_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'delivered' => 'Delivered',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowerOrders::route('/'),
            'create' => Pages\CreateFlowerOrder::route('/create'),
            'edit' => Pages\EditFlowerOrder::route('/{record}/edit'),
        ];
    }
}
