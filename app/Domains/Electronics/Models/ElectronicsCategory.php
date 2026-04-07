<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Carbon\Carbon;
use HasFactory, SoftDeletes;
use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
     * ElectronicsCategory - Product classification.
     */
final class ElectronicsCategory extends Model
{
        use HasFactory;

        protected $table = 'electronics_categories';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'slug',
            'icon',
            'correlation_id',
        ];

        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?: (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?: (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function products(): HasMany
        {
            return $this->hasMany(ElectronicsProduct::class, 'category_id');
        }
    }
