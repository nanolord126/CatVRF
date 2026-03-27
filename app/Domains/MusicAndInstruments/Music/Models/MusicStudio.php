<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * MusicStudio model representing a recording studio or practice room.
 */
final class MusicStudio extends Model
{
    use HasFactory;

    protected $table = 'music_studios';

    protected $fillable = [
        'uuid',
        'music_store_id',
        'tenant_id',
        'correlation_id',
        'name',
        'description',
        'price_per_hour_cents',
        'min_booking_hours',
        'equipment',
        'has_engineer',
        'tags',
    ];

    protected $casts = [
        'equipment' => 'json',
        'tags' => 'array',
        'price_per_hour_cents' => 'integer',
        'min_booking_hours' => 'integer',
        'has_engineer' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
            
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()->id ?? 'null';
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('music_studios.tenant_id', tenant()->id);
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(MusicStore::class, 'music_store_id');
    }

    public function bookings(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(MusicBooking::class, 'bookable');
    }
}
