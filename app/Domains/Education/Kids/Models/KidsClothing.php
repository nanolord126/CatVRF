<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsClothing extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'kids_clothing';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'product_id',
            'clothing_size',
            'gender', // boy, girl, unisex
            'fabric_composition',
            'is_hypoallergenic',
            'season_type', // summer, winter, autumn, spring
            'style_tag',
            'care_instructions',
            'correlation_id',
        ];

        protected $casts = [
            'is_hypoallergenic' => 'boolean',
            'fabric_composition' => 'json', // cotton, wool, silk
            'care_instructions' => 'json', // temp, bleach, iron
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
         * Product relationship.
         */
        public function product(): BelongsTo
        {
            return $this->belongsTo(KidsProduct::class, 'product_id');
        }

        /**
         * Filter by safety (hypoallergenic).
         */
        public function scopeSafeForSensitiveSkin(Builder $query): Builder
        {
            return $query->where('is_hypoallergenic', true);
        }

        /**
         * Season filter.
         */
        public function scopeForWinter(Builder $query): Builder
        {
            return $query->where('season_type', 'winter');
        }

        /**
         * Gender filter.
         */
        public function scopeForGirls(Builder $query): Builder
        {
            return $query->whereIn('gender', ['girl', 'unisex']);
        }

        /**
         * Check if clothing contains cotton.
         */
        public function isCottonRich(): bool
        {
            return isset($this->fabric_composition['cotton']) && $this->fabric_composition['cotton'] >= 90;
        }
}
