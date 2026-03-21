<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources;

use App\Domains\Food\Models\RestaurantOrder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

/**
 * Filament Resource для управления заказами.
 * Production 2026.
 */
final class RestaurantOrderResource extends Resource
{
    protected static ?string $model = RestaurantOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Food & Delivery';

    protected static ?string $label = 'Заказ';

    protected static ?string $pluralLabel = 'Заказы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Информация о заказе')
                    ->schema([
                        TextColumn::make('order_number')
                            ->label('Номер заказа'),
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'В ожидании',
                                'confirmed' => 'Подтверждён',
                                'cooking' => 'Готовится',
                                'ready' => 'Готов',
                                'delivered' => 'Доставлен',
                                'cancelled' => 'Отменён',
                            ]),
                        Textarea::make('customer_notes')
                            ->label('Примечания клиента'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('restaurant.name')
                    ->label('Ресторан')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'delivered',
                        'warning' => 'ready',
                        'info' => 'cooking',
                        'secondary' => 'pending',
                        'danger' => 'cancelled',
                    ]),
                TextColumn::make('total_price')
                    ->label('Сумма')
                    ->money('RUB', 100),
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
                        'confirmed' => 'Подтверждён',
                        'cooking' => 'Готовится',
                        'ready' => 'Готов',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Food\Filament\Resources\RestaurantOrderResource\Pages\ListRestaurantOrders::route('/'),
        ];
    }
}
