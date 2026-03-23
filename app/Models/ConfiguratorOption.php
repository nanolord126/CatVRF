<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class ConfiguratorOption extends Model
{
    protected $table = 'configurator_options';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'template_id',
        'category',
        'name',
        'sku',
        'price_kopeks',
        'weight_grams',
        'volume_cm3',
        'properties',
        'compatibility_rules',
        'correlation_id',
    ];

    protected $casts = [
        'properties' => 'json',
        'compatibility_rules' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (ConfiguratorOption $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ConfiguratorTemplate::class, 'template_id');
    }
}
