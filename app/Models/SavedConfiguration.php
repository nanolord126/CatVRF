<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class SavedConfiguration extends Model
{
    protected $table = 'saved_configurations';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'template_id',
        'project_name',
        'payload',
        'total_price_kopeks',
        'total_weight_grams',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (SavedConfiguration $model) {
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
