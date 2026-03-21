<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Заказ-наряд на услуги СТО.
 * Production 2026.
 */
final class AutoServiceOrder extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'auto_service_orders';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'car_brand',
        'car_model',
        'service_id',
        'status',
        'total_price',
        'appointment_datetime',
        'completed_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'total_price' => 'integer',
        'appointment_datetime' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'client_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AutoService::class, 'service_id');
    }
}
