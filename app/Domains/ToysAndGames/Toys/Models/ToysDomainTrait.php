<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Models;

use HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use ToysDomainTrait;

final class ToysDomainTrait extends Model
{
    use HasFactory;
    protected static function booted(): void
        {
            // 1. Automatic UUID and Correlation Logic
            static::creating(function ($model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (property_exists($model, 'correlation_id') && empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
            });

            // 2. Global Multi-tenant Scoping (Lute Mode - No leaks)
            static::addGlobalScope('tenant_isolation', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }
    }
