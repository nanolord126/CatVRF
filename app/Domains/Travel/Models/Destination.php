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
 * КАНОН 2026: Модель Destination (Направление).
 */
final class Destination extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'destinations';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'country_code',
        'description',
        'geo_point',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'geo_point' => 'json',
        'tags' => 'json',
        'deleted_at' => 'datetime'
    ];

    protected static function booted(): void
    {
        static::creating(function (Destination $model) {
            if (!$model->uuid) $model->uuid = (string) Str::uuid();
            if (!$model->tenant_id) $model->tenant_id = (tenant()->id ?? 1);
            if (!$model->correlation_id) $model->correlation_id = request()->header('X-Correlation-ID');
        });

        static::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function excursions(): HasMany
    {
        return $this->hasMany(Excursion::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'country_code', 'description'])
            ->logOnlyDirty()
            ->useLogName('travel_domain')
            ->dontSubmitEmptyLogs();
    }
}
