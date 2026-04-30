<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use Carbon\CarbonImmutable;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;

final class ShipmentResource extends BaseOptimizedResource
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
            TextColumn::make('status')->badge(),
            TextColumn::make('delivered_at')->sortable(),
        ])->filters([])->actions([])->bulkActions([]);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    /**
     * Relations to eager load for Logistics
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
