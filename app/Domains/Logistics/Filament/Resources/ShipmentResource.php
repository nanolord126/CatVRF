<?php

declare(strict_types=1);


namespace App\Domains\Logistics\Filament\Resources;

use App\Domains\Logistics\Models\Shipment;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

final /**
 * ShipmentResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentResource extends Resource
{
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
