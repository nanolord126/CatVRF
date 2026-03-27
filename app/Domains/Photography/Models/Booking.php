<?php

declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — BOOKING MODEL (Photography)
 * 1. status enum: pending, confirmed, paid, completed, cancelled, rescheduled
 * 2. total_amount_kopecks
 */
final class Booking extends Model
{
    use SoftDeletes;

    protected $table = 'photography_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'client_id',
        'session_id',
        'photographer_id',
        'studio_id',
        'starts_at',
        'ends_at',
        'status',
        'total_amount_kopecks',
        'paid_amount_kopecks',
        'idempotency_key',
        'correlation_id'
    ];

    protected $casts = [
        'uuid' => 'string',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'total_amount_kopecks' => 'integer',
        'paid_amount_kopecks' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->tenant_id ??= tenant()?->id;
            $model->idempotency_key ??= (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PhotoSession::class, 'session_id');
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(Photographer::class, 'photographer_id');
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(PhotoStudio::class, 'studio_id');
    }
}
