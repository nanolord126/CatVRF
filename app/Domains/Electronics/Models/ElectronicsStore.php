<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Carbon\Carbon;
use HasFactory, SoftDeletes;
use HasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicsStore extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'electronics_stores';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'address',
            'working_hours',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'working_hours' => 'json',
            'tags' => 'json',
        ];

        /**
         * Global Scope: Tenant Isolation.
         */
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

        /* --- Relations --- */

        public function products(): HasMany
        {
            return $this->hasMany(ElectronicsProduct::class, 'store_id');
        }

        /* --- Helpers --- */

        public function getIsOpenAttribute(): bool
        {
            // Simple mock for logic, real imp would check current time vs working_hours JSON
            return true;
        }
    }
