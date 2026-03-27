<?php

declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — REVIEW MODEL (Photography)
 */
final class Review extends Model
{
    protected $table = 'photography_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'booking_id',
        'photographer_id',
        'studio_id',
        'user_id',
        'rating',
        'comment',
        'photos',
        'correlation_id'
    ];

    protected $casts = [
        'uuid' => 'string',
        'photos' => 'json',
        'rating' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->tenant_id ??= tenant()?->id;
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
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
