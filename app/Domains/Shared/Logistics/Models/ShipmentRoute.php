<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentRoute extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'shipment_routes';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'courier_service_id',
        'waypoints',
        'total_distance',
        'estimated_time_minutes',
        'is_optimized',
        'correlation_id',
    ];

    protected $casts = [
        'waypoints' => 'collection',
        'total_distance' => 'float',
        'is_optimized' => 'boolean',
    ];

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

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()?->id);
            }
        });
    }
}
