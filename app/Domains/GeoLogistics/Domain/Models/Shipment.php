<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Domain\Models;

use App\Domains\GeoLogistics\Domain\Enums\ShipmentStatus;
use App\Domains\GeoLogistics\Domain\Events\ShipmentStatusChangedEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Доменная модель доставки (Shipment). 
 * Инкапсулирует логику транзита, интеграции с картой и курьером.
 */
final class Shipment extends Model
{
    protected $table = 'geo_shipments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'delivery_order_id', // Интеграция с доменом Delivery / Food
        'courier_id',
        'status',
        'pickup_lat',
        'pickup_lng',
        'dropoff_lat',
        'dropoff_lng',
        'current_lat',
        'current_lng',
        'estimated_distance_meters',
        'estimated_duration_seconds',
        'calculated_cost',
        'correlation_id'
    ];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'pickup_lat' => 'float',
        'pickup_lng' => 'float',
        'dropoff_lat' => 'float',
        'dropoff_lng' => 'float',
        'current_lat' => 'float',
        'current_lng' => 'float',
        'estimated_distance_meters' => 'integer',
        'estimated_duration_seconds' => 'integer',
        'calculated_cost' => 'integer', // В копейках
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()->id) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Безоговорочная мутация статуса со строгим порождением доменного события.
     */
    public function transitionTo(ShipmentStatus $newStatus, string $correlationId): void
    {
        $oldStatus = $this->status;
        if ($oldStatus === $newStatus) {
            return;
        }

        $this->status = $newStatus;
        $this->save();

        event(new ShipmentStatusChangedEvent($this->id, $oldStatus, $newStatus, $correlationId));
    }
}
