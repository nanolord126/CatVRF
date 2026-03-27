<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель DeliveryOrder (Заказ доставки).
 * Слой 2: Доменные модели.
 */
final class DeliveryOrder extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'delivery_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'sender_id',
        'courier_id',
        'status',
        'package_type',
        'weight_kg',
        'recipient_name',
        'recipient_phone',
        'pickup_point',
        'dropoff_point',
        'price',
        'correlation_id',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'json',
        'weight_kg' => 'float',
        'price' => 'integer',
        'tenant_id' => 'integer',
        'sender_id' => 'integer',
        'courier_id' => 'integer'
    ];

    /**
     * Глобальный скоупинг тенанта.
     */
    protected static function booted(): void
    {
        static::creating(function (DeliveryOrder $order) {
            $order->uuid = $order->uuid ?? (string) Str::uuid();
            $order->tenant_id = $order->tenant_id ?? (tenant()->id ?? 1);
            $order->correlation_id = $order->correlation_id ?? request()->header('X-Correlation-ID');
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Настройка логов активности.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'courier_id', 'price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setLogName('delivery_events');
    }

    /**
     * Отношения.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'courier_id');
    }

    /**
     * Форматирование цены (копейки -> рубли).
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price / 100, 2, '.', ' ') . ' ₽';
    }
}
