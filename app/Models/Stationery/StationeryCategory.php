<?php

declare(strict_types=1);

namespace App\Models\Stationery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * StationeryCategory Model.
 */
final class StationeryCategory extends Model
{
    protected $table = 'stationery_categories';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_active',
        'correlation_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            $model->slug = Str::slug($model->name);
            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(StationeryProduct::class, 'category_id');
    }
}
