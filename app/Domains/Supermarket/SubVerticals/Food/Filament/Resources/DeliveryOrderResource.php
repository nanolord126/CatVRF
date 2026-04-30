<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Filament\Resources;

use App\Domains\Food\Models\DeliveryOrder;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Domains\Food\Filament\Resources\DeliveryOrderResource\Pages\ListDeliveryOrders;

final class DeliveryOrderResource extends BaseOptimizedResource
{
    protected static ?string $model = DeliveryOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Food & Delivery';

    protected static ?string $label = 'Доставка';

    protected static ?string $pluralLabel = 'Доставки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Информация о доставке')
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'В ожидании',
                                'accepted' => 'Принята',
                                'on_way' => 'В пути',
                                'delivered' => 'Доставлена',
                                'cancelled' => 'Отменена',
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->searchable(),
                TextColumn::make('customer_address')
                    ->label('Адрес доставки')
                    ->searchable(),
                TextColumn::make('status')->badge()
                    ->label('Статус')
                    ->colors([
                        'success' => 'delivered',
                        'info' => 'on_way',
                        'warning' => 'pending',
                        'danger' => 'cancelled',
                    ]),
                TextColumn::make('distance_km')
                    ->label('Расстояние')
                    ->suffix(' км'),
                TextColumn::make('eta_minutes')
                    ->label('ETA')
                    ->suffix(' мин'),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В ожидании',
                        'accepted' => 'Принята',
                        'on_way' => 'В пути',
                        'delivered' => 'Доставлена',
                        'cancelled' => 'Отменена',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryOrders::route('/'),
        ];
    }

    /**
     * Relations to eager load for Food
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
