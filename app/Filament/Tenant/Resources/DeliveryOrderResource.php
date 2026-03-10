<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\DeliveryOrderResource\Pages;
use Modules\Delivery\Models\DeliveryOrder;
use Modules\Delivery\Models\DeliveryZone;
use Modules\Delivery\Services\DeliveryCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Str;

use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Actions\Action as InfolistAction;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Delivery';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Статус и SLA')
                            ->schema([
                                ViewEntry::make('status_stepper')
                                    ->label('')
                                    ->view('filament.tenant.components.order-stepper'),
                                ViewEntry::make('sla_timer')
                                    ->label('')
                                    ->view('filament.tenant.components.sla-timer'),
                            ])
                            ->columnSpan(1),
                        Section::make('Трекинг курьера (Live)')
                            ->schema([
                                ViewEntry::make('courier_map')
                                    ->label('')
                                    ->view('filament.tenant.components.courier-map'),
                            ])
                            ->columnSpan(2),
                    ]),
                Section::make('Детали доставки')
                    ->schema([
                        TextEntry::make('tracking_number')
                            ->badge()
                            ->color('info')
                            ->label('Номер заказа'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'in_progress' => 'info',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('delivery_fee')
                            ->money('RUB')
                            ->label('Стоимость доставки'),
                        TextEntry::make('deliveryZone.name')
                            ->label('Зона доставки')
                            ->placeholder('Не определена'),
                    ])->columns(4),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Delivery Information')
                    ->schema([
                        Components\TextInput::make('tracking_number')
                            ->default(fn () => 'TRK-' . Str::upper(Str::random(10)))
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextInput::make('target_lat')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateDeliveryFee($get, $set)),
                                Components\TextInput::make('target_lng')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateDeliveryFee($get, $set)),
                                Components\Select::make('delivery_zone_id')
                                    ->label('Delivery Zone (Auto)')
                                    ->options(DeliveryZone::all()->pluck('name', 'id'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('System will select zone'),
                            ]),
                        Components\TextInput::make('delivery_fee')
                            ->numeric()
                            ->prefix('₽')
                            ->required()
                            ->helperText('Automatically calculated based on the delivery zone.'),
                    ])
            ]);
    }

    protected static function updateDeliveryFee(Get $get, Set $set): void
    {
        $lat = $get('target_lat');
        $lng = $get('target_lng');

        if (!$lat || !$lng) return;

        $zones = DeliveryZone::all();
        $calculator = new DeliveryCalculator();
        $bestZone = null;
        $minPrice = PHP_INT_MAX;

        foreach ($zones as $zone) {
            $cost = $calculator->getDeliveryCost((float)$lat, (float)$lng, $zone);
            if ($cost !== null && $cost < $minPrice) {
                $minPrice = $cost;
                $bestZone = $zone;
            }
        }

        if ($bestZone) {
            $set('delivery_fee', $minPrice);
            $set('delivery_zone_id', $bestZone->id);
        } else {
            $set('delivery_fee', 0);
            $set('delivery_zone_id', null);
            // Optional: Notification that location is out of any delivery zone
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('tracking_number')
                    ->searchable()
                    ->sortable(),
                Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                Columns\TextColumn::make('delivery_fee')
                    ->money('RUB')
                    ->sortable(),
                Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'delivered' => 'Delivered',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('mark_delivered')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (DeliveryOrder $record) => $record->update(['status' => 'delivered']))
                        ->visible(fn (DeliveryOrder $record) => $record->status !== 'delivered'),
                ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDeliveryOrders::route('/'),
        ];
    }
}
