<?php declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use Filament\Resources\Resource;

final class ShipmentTrackingResource extends Resource
{

    protected static ?string $model = ShipmentTracking::class;

        protected static ?string $navigationGroup = 'Logistics';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Select::make('shipment_id')->relationship('shipment', 'tracking_number')->required(),
                Select::make('event_type')->options([
                    'picked_up' => 'Picked Up',
                    'in_transit' => 'In Transit',
                    'out_for_delivery' => 'Out for Delivery',
                    'delivered' => 'Delivered',
                    'failed' => 'Failed',
                    'returned' => 'Returned',
                ])->required(),
                TextInput::make('location'),
                Textarea::make('notes'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('shipment.tracking_number'),
                TextColumn::make('event_type')->badge(),
                TextColumn::make('location'),
                TextColumn::make('event_time')->sortable(),
            ])->filters([])->actions([])->bulkActions([]);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
