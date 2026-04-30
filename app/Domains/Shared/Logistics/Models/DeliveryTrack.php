<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeliveryTrack — запись реал-тайм геотрекинга курьера.
 *
 * Хранит каждое обновление координат в delivery_tracks.
 * Для аналитики дополнительно пишется в ClickHouse (через BigDataAggregatorService).
 */
final class DeliveryTrack extends Model
{
    use TenantScoped;

    public $timestamps = false;

    protected $table = 'delivery_tracks';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'delivery_order_id',
        'courier_id',
        'lat',
        'lon',
        'speed',
        'bearing',
        'correlation_id',
        'tracked_at',
    ];

    protected $casts = [
        'lat'        => 'decimal:8',
        'lon'        => 'decimal:8',
        'speed'      => 'decimal:2',
        'bearing'    => 'decimal:2',
        'tracked_at' => 'datetime',
    ];

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(static function (self $model): void {
            if (empty($model->tracked_at)) {
                $model->tracked_at = CarbonImmutable::now();
            }
        });
    }
}
