<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryZone extends Model
{


    use HasFactory, SoftDeletes;

        protected $table = 'delivery_zones';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'courier_service_id',
            'zone_name',
            'polygon',
            'surge_multiplier',
            'estimated_delivery_hours',
            'is_active',
            'correlation_id',
        ];

        protected $casts = [
            'surge_multiplier' => 'float',
            'is_active' => 'boolean',
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
