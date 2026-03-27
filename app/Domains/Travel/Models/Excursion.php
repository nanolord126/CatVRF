<?php

declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель Excursion.
 */
final class Excursion extends Model
{
    use LogsActivity;

    protected $table = 'excursions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'destination_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'price' => 'integer',
        'duration_minutes' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (Excursion $model) {
            if (!$model->uuid) $model->uuid = (string) Str::uuid();
            if (!$model->tenant_id) $model->tenant_id = (tenant()->id ?? 1);
            if (!$model->correlation_id) $model->correlation_id = request()->header('X-Correlation-ID');
        });

        static::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'duration_minutes'])
            ->logOnlyDirty()
            ->useLogName('travel_domain')
            ->dontSubmitEmptyLogs();
    }
}
