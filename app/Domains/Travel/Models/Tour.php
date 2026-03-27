<?php

declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель Tour (Тур).
 */
final class Tour extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tours';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'destination_id',
        'title',
        'content',
        'base_price',
        'duration_days',
        'difficulty',
        'amenities',
        'tags',
        'is_active',
        'correlation_id'
    ];

    protected $casts = [
        'amenities' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
        'base_price' => 'integer',
        'deleted_at' => 'datetime'
    ];

    protected static function booted(): void
    {
        static::creating(function (Tour $model) {
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

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'base_price', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('travel_domain')
            ->dontSubmitEmptyLogs();
    }
}
