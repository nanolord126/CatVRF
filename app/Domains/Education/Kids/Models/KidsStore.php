<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsStore extends Model
{

    use HasFactory, SoftDeletes;

        protected $table = 'kids_stores';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'type',
            'address',
            'geo_point',
            'schedule',
            'rating',
            'review_count',
            'is_verified',
            'safety_certificates',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'geo_point' => 'json',
            'schedule' => 'json',
            'safety_certificates' => 'json',
            'tags' => 'json',
            'is_verified' => 'boolean',
            'rating' => 'decimal:2',
        ];

        /**
         * Boot the model with tenant and correlation scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (string) (tenant()->id ?? 'system');
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * All products available in this store.
         */
        public function products(): HasMany
        {
            return $this->hasMany(KidsProduct::class, 'store_id');
        }

        /**
         * Get verified stores filter.
         */
        public function scopeVerified(Builder $query): Builder
        {
            return $query->where('is_verified', true);
        }

        /**
         * Filter by boutique or center type.
         */
        public function scopeOfType(Builder $query, string $type): Builder
        {
            return $query->where('type', $type);
        }

        /**
         * High rating filter (>= 4.5).
         */
        public function scopeHighRated(Builder $query): Builder
        {
            return $query->where('rating', '>=', 4.5);
        }

        /**
         * Global UUID search helper.
         */
        public static function findByUuid(string $uuid): ?self
        {
            return self::where('uuid', $uuid)->first();
        }
}
