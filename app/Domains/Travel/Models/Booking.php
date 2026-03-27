<?php

declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель Booking (Бронирование).
 */
final class Booking extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'travel_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'bookable_type',
        'bookable_id',
        'slots_count',
        'total_price',
        'status',
        'payment_status',
        'idempotency_key',
        'correlation_id',
        'metadata'
    ];

    protected $casts = [
        'total_price' => 'integer',
        'slots_count' => 'integer',
        'metadata' => 'json',
        'deleted_at' => 'datetime'
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $model) {
            if (!$model->uuid) $model->uuid = (string) Str::uuid();
            if (!$model->tenant_id) $model->tenant_id = (tenant()->id ?? 1);
            if (!$model->correlation_id) $model->correlation_id = request()->header('X-Correlation-ID') ?? (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'payment_status', 'total_price'])
            ->logOnlyDirty()
            ->useLogName('travel_domain')
            ->dontSubmitEmptyLogs();
    }
}
