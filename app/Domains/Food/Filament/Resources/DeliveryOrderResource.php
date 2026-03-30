<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryOrderResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                    BadgeColumn::make('status')
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
                'index' => \App\Domains\Food\Filament\Resources\DeliveryOrderResource\Pages\ListDeliveryOrders::route('/'),
            ];
        }
}
