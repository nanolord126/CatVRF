<?php declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Shipment::class;

        protected static ?string $navigationGroup = 'Logistics';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Select::make('customer_id')->relationship('customer', 'name')->required(),
                Select::make('courier_service_id')->relationship('courierService', 'company_name')->nullable(),
                TextInput::make('origin_address')->required(),
                TextInput::make('destination_address')->required(),
                TextInput::make('weight')->required()->numeric()->step(0.01),
                TextInput::make('declared_value')->required()->numeric()->step(0.01),
                TextInput::make('shipping_cost')->required()->numeric()->step(0.01),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('tracking_number')->searchable(),
                TextColumn::make('customer.email'),
                TextColumn::make('origin_address'),
                BadgeColumn::make('status'),
                TextColumn::make('delivered_at')->sortable(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}
