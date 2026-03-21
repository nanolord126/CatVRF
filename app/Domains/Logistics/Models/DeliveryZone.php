<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DeliveryZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'delivery_zones';

    protected $fillable = [
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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function courierService(): BelongsTo
    {
        return $this->belongsTo(CourierService::class);
    }
}
