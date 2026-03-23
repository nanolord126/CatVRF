<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class ConfiguratorTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'configurator_templates';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'type',
        'meta',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'meta' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ConfiguratorTemplate $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });
    }

    public function options(): HasMany
    {
        return $this->hasMany(ConfiguratorOption::class, 'template_id');
    }
}
