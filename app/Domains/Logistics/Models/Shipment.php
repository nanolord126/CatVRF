<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Shipment extends Model
{

    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'shipments';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'business_group_id',
            'courier_service_id',
            'customer_id',
            'tracking_number',
            'origin_address',
            'destination_address',
            'origin_location',
            'destination_location',
            'weight',
            'declared_value',
            'shipping_cost',
            'commission_amount',
            'status',
            'picked_up_at',
            'delivered_at',
            'cancelled_at',
            'cancellation_reason',
            'transaction_id',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'weight' => 'float',
            'declared_value' => 'float',
            'shipping_cost' => 'float',
            'commission_amount' => 'float',
            'tags' => 'collection',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()?->id);
                }
            });
        }

        public function courierService(): BelongsTo
        {
            return $this->belongsTo(CourierService::class);
        }

        public function customer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }

        public function tracking(): HasMany
        {
            return $this->hasMany(ShipmentTracking::class);
        }

        public function ratings(): HasMany
        {
            return $this->hasMany(ShipmentRating::class);
        }

        public function insurance(): HasMany
        {
            return $this->hasMany(ShipmentInsurance::class);
        }
}
