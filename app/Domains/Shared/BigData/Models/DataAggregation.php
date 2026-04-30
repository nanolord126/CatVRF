<?php

declare(strict_types=1);

namespace App\Domains\BigData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use Illuminate\Support\Str;

final class DataAggregation extends Model
{
    protected $fillable = [
        'tenant_id',
        'source',
        'aggregation_type',
        'aggregation_key',
        'value',
        'timestamp',
        'metadata',
    ];

    protected $casts = [
        'value' => 'float',
        'timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('aggregation_type', $type);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
