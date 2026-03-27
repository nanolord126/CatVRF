<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

final class Property extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'properties';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'correlation_id',
        'name',
        'address',
        'lat',
        'lon',
        'type',
        'area',
        'rooms',
        'floor',
        'features',
        'status',
        'tags',
    ];

    protected $casts = [
        'features' => 'array',
        'tags' => 'array',
        'lat' => 'float',
        'lon' => 'float',
        'area' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Property $model) {
             if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
             if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function listings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('real_estate')
            ->logOnlyDirty();
    }
}
