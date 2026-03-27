<?php

declare(strict_types=1);


namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * ShipmentTracking
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentTracking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shipment_tracking';

    protected $fillable = [
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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
