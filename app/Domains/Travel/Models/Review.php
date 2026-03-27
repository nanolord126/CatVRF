<?php

declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель Review.
 */
final class Review extends Model
{
    use LogsActivity;

    protected $table = 'travel_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'booking_id',
        'user_id',
        'rating',
        'comment',
        'photos',
        'is_verified',
        'correlation_id'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'photos' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (Review $model) {
            if (!$model->uuid) $model->uuid = (string) Str::uuid();
            if (!$model->tenant_id) $model->tenant_id = (tenant()->id ?? 1);
            if (!$model->correlation_id) $model->correlation_id = (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['rating', 'comment', 'is_verified'])
            ->logOnlyDirty()
            ->useLogName('travel_domain')
            ->dontSubmitEmptyLogs();
    }
}
