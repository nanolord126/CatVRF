<?php declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentTrackingResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                BadgeColumn::make('event_type'),
                TextColumn::make('location'),
                TextColumn::make('event_time')->sortable(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}
