<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics;

use App\Domains\Logistics\Models\OrderShipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * OrderShipmentResource — Filament ресурс для управления отправками
 *
 * Позволяет администраторам tenant'а управлять отправками заказов.
 */
final class OrderShipmentResource extends Resource
{
    protected static ?string $model = OrderShipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Logistics';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о заказе')
                    ->schema([
                        Forms\Components\TextInput::make('order_id')
                            ->required()
                            ->numeric()
                            ->label('ID заказа'),

                        Forms\Components\Select::make('fulfillment_type')
                            ->options([
                                OrderShipment::FULFILLMENT_COURIER => 'Курьер',
                                OrderShipment::FULFILLMENT_PICKUP_POINT => 'ПВЗ',
                                OrderShipment::FULFILLMENT_TAXI => 'Такси',
                            ])
                            ->required()
                            ->live()
                            ->label('Тип фулфилмента'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Назначение')
                    ->schema([
                        Forms\Components\Select::make('courier_id')
                            ->relationship('courier', 'id')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => in_array($get('fulfillment_type'), ['courier', 'taxi'], true))
                            ->label('Курьер'),

                        Forms\Components\Select::make('pickup_point_id')
                            ->relationship('pickupPoint', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('fulfillment_type') === 'pickup_point')
                            ->label('ПВЗ'),
                    ]),

                Forms\Components\Section::make('Статус и ETA')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                OrderShipment::STATUS_PENDING => 'Ожидает',
                                OrderShipment::STATUS_ASSIGNED => 'Назначен',
                                OrderShipment::STATUS_PICKED => 'Забран',
                                OrderShipment::STATUS_IN_TRANSIT => 'В пути',
                                OrderShipment::STATUS_DELIVERED => 'Доставлен',
                                OrderShipment::STATUS_ISSUED_AT_PVZ => 'Выдан в ПВЗ',
                                OrderShipment::STATUS_CANCELLED => 'Отменен',
                                OrderShipment::STATUS_FAILED => 'Ошибка',
                            ])
                            ->required()
                            ->label('Статус'),

                        Forms\Components\TextInput::make('eta_minutes')
                            ->numeric()
                            ->minValue(0)
                            ->label('ETA (минуты)'),

                        Forms\Components\DateTimePicker::make('assigned_at')
                            ->label('Назначен'),

                        Forms\Components\DateTimePicker::make('picked_at')
                            ->label('Забран'),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Доставлен'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Коды ПВЗ')
                    ->schema([
                        Forms\Components\TextInput::make('pickup_code')
                            ->maxLength(4)
                            ->label('Код выдачи'),

                        Forms\Components\TextInput::make('qr_code')
                            ->maxLength(255)
                            ->label('QR код'),

                        Forms\Components\DateTimePicker::make('issued_at_pvz')
                            ->label('Выдан в ПВЗ'),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('fulfillment_type') === 'pickup_point')
                    ->columns(3),

                Forms\Components\Section::make('Маршрут')
                    ->schema([
                        Forms\Components\Textarea::make('route_polyline')
                            ->rows(3)
                            ->label('Полилиния маршрута'),

                        Forms\Components\TextInput::make('distance_km')
                            ->numeric()
                            ->step(0.01)
                            ->label('Расстояние (км)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),

                Tables\Columns\TextColumn::make('order.id')
                    ->sortable()
                    ->searchable()
                    ->label('Заказ'),

                Tables\Columns\BadgeColumn::make('fulfillment_type')
                    ->colors([
                        'primary' => OrderShipment::FULFILLMENT_COURIER,
                        'success' => OrderShipment::FULFILLMENT_PICKUP_POINT,
                        'warning' => OrderShipment::FULFILLMENT_TAXI,
                    ])
                    ->label('Тип'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => OrderShipment::STATUS_PENDING,
                        'info' => OrderShipment::STATUS_ASSIGNED,
                        'warning' => OrderShipment::STATUS_PICKED,
                        'primary' => OrderShipment::STATUS_IN_TRANSIT,
                        'success' => [OrderShipment::STATUS_DELIVERED, OrderShipment::STATUS_ISSUED_AT_PVZ],
                        'danger' => [OrderShipment::STATUS_CANCELLED, OrderShipment::STATUS_FAILED],
                    ])
                    ->label('Статус'),

                Tables\Columns\TextColumn::make('courier.id')
                    ->toggleable()
                    ->label('Курьер'),

                Tables\Columns\TextColumn::make('pickupPoint.name')
                    ->toggleable()
                    ->limit(20)
                    ->label('ПВЗ'),

                Tables\Columns\TextColumn::make('eta_minutes')
                    ->numeric()
                    ->suffix(' мин')
                    ->toggleable()
                    ->label('ETA'),

                Tables\Columns\TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->label('Назначен'),

                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Доставлен'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        OrderShipment::STATUS_PENDING => 'Ожидает',
                        OrderShipment::STATUS_ASSIGNED => 'Назначен',
                        OrderShipment::STATUS_PICKED => 'Забран',
                        OrderShipment::STATUS_IN_TRANSIT => 'В пути',
                        OrderShipment::STATUS_DELIVERED => 'Доставлен',
                        OrderShipment::STATUS_ISSUED_AT_PVZ => 'Выдан в ПВЗ',
                        OrderShipment::STATUS_CANCELLED => 'Отменен',
                        OrderShipment::STATUS_FAILED => 'Ошибка',
                    ])
                    ->label('Статус'),

                Tables\Filters\SelectFilter::make('fulfillment_type')
                    ->options([
                        OrderShipment::FULFILLMENT_COURIER => 'Курьер',
                        OrderShipment::FULFILLMENT_PICKUP_POINT => 'ПВЗ',
                        OrderShipment::FULFILLMENT_TAXI => 'Такси',
                    ])
                    ->label('Тип фулфилмента'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('assigned_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderShipments::route('/'),
            'create' => Pages\CreateOrderShipment::route('/create'),
            'view' => Pages\ViewOrderShipment::route('/{record}'),
            'edit' => Pages\EditOrderShipment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
