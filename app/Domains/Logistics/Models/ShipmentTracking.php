<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentTracking extends Model
{

    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'shipment_tracking';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'shipment_id',
            'event_type',
            'location',
            'location_point',
            'notes',
            'event_time',
            'correlation_id',
        ];

        protected $casts = [
            'event_time' => 'datetime',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()?->id);
                }
            });
        }

        public function shipment(): BelongsTo
        {
            return $this->belongsTo(Shipment::class);
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
