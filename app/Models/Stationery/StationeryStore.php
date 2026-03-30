<?php declare(strict_types=1);

namespace App\Models\Stationery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StationeryStore extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'stationery_stores';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'description',
            'city',
            'rating',
            'is_active',
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'metadata' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'rating' => 'float',
        ];

        /**
         * Boot logic for automatic UUID and tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
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
            return $this->hasMany(StationeryProduct::class, 'store_id');
        }

        public function giftSets(): HasMany
        {
            return $this->hasMany(StationeryGiftSet::class, 'store_id');
        }

        public function businessGroup(): BelongsTo
        {
            return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
        }

        public function tenant(): BelongsTo
        {
            return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
        }
}
