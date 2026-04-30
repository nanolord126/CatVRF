<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Models\Courier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Domains\Logistics\Filament\Resources\OrderShipmentResource\Pages\CreateOrderShipment;
use App\Domains\Logistics\Filament\Resources\OrderShipmentResource\Pages\EditOrderShipment;
use App\Domains\Logistics\Filament\Resources\OrderShipmentResource\Pages\ListOrderShipments;
use App\Domains\Logistics\Filament\Resources\OrderShipmentResource\Pages\ViewOrderShipment;

/**
 * Order Shipment Filament Resource
 *
 * Admin interface for managing order shipments.
 * Supports both courier and pickup point fulfillment.
 *
 * Features:
 * - View all shipments with status badges
 * - Filter by status, fulfillment type
 * - View courier/PVZ details
 * - Track shipment progress
 * - View QR codes and pickup codes
 */
final class OrderShipmentResource extends Resource
{
    protected static ?string $model = OrderShipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Logistics';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'uuid';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipment Information')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->maxLength(255),

                        Forms\Components\Select::make('order_id')
                            ->label('Order')
                            ->relationship('order', 'uuid')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('courier_id')
                            ->label('Courier')
                            ->relationship('courier', 'id')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('pickup_point_id')
                            ->label('Pickup Point (PVZ)')
                            ->relationship('pickupPoint', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'assigned' => 'Assigned',
                                'picked' => 'Picked',
                                'in_transit' => 'In Transit',
                                'delivered' => 'Delivered',
                                'issued_at_pvz' => 'Issued at PVZ',
                                'cancelled' => 'Cancelled',
                                'failed' => 'Failed',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('eta_minutes')
                            ->label('ETA (minutes)')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('distance_km')
                            ->label('Distance (km)')
                            ->numeric()
                            ->step(0.01)
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('PVZ Information')
                    ->schema([
                        Forms\Components\TextInput::make('qr_code')
                            ->label('QR Code')
                            ->disabled()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pickup_code')
                            ->label('Pickup Code')
                            ->disabled()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->visible(fn (OrderShipment $record) => $record->fulfillment_type === 'pickup_point'),

                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('assigned_at')
                            ->label('Assigned At')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('picked_at')
                            ->label('Picked At')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Delivered At')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('issued_at_pvz')
                            ->label('Issued at PVZ')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('order.uuid')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'blue' => 'assigned',
                        'warning' => 'picked',
                        'info' => 'in_transit',
                        'success' => ['delivered', 'issued_at_pvz'],
                        'danger' => ['cancelled', 'failed'],
                    ]),

                BadgeColumn::make('fulfillment_type')
                    ->label('Fulfillment Type')
                    ->colors([
                        'primary' => 'courier',
                        'success' => 'pickup_point',
                        'warning' => 'taxi',
                    ]),

                TextColumn::make('courier.id')
                    ->label('Courier ID')
                    ->toggleable(),

                TextColumn::make('pickupPoint.name')
                    ->label('PVZ')
                    ->toggleable()
                    ->limit(30),

                TextColumn::make('eta_minutes')
                    ->label('ETA (min)')
                    ->toggleable(),

                TextColumn::make('distance_km')
                    ->label('Distance (km)')
                    ->toggleable(),

                TextColumn::make('pickup_code')
                    ->label('Pickup Code')
                    ->toggleable()
                    ->copyable(),

                TextColumn::make('assigned_at')
                    ->label('Assigned At')
                    ->dateTime()
                    ->toggleable(),

                TextColumn::make('delivered_at')
                    ->label('Delivered At')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'assigned' => 'Assigned',
                        'picked' => 'Picked',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'issued_at_pvz' => 'Issued at PVZ',
                        'cancelled' => 'Cancelled',
                        'failed' => 'Failed',
                    ]),

                SelectFilter::make('fulfillment_type')
                    ->options([
                        'courier' => 'Courier',
                        'pickup_point' => 'Pickup Point',
                        'taxi' => 'Taxi',
                    ]),

                SelectFilter::make('courier_id')
                    ->label('Courier')
                    ->relationship('courier', 'id')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('pickup_point_id')
                    ->label('Pickup Point')
                    ->relationship('pickupPoint', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'order' => Tables\RelationManagers\RelationManager::class,
            'courier' => Tables\RelationManagers\RelationManager::class,
            'pickupPoint' => Tables\RelationManagers\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderShipments::route('/'),
            'create' => CreateOrderShipment::route('/create'),
            'view' => ViewOrderShipment::route('/{record}'),
            'edit' => EditOrderShipment::route('/{record}/edit'),
        ];
    }
}
