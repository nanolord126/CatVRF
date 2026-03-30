<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautySalon extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $table = 'beauty_salons';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'address',
            'phone',
            'email',
            'description',
            'working_hours',
            'geo_point',
            'rating',
            'review_count',
            'is_verified',
            'tags',
            'metadata',
            'correlation_id',
        ];

        protected $casts = [
            'working_hours' => 'json',
            'geo_point' => 'json',
            'tags' => 'json',
            'metadata' => 'json',
            'is_verified' => 'boolean',
            'rating' => 'float',
            'review_count' => 'integer',
            'deleted_at' => 'datetime',
        ];

        /**
         * Инициальзация модели - глобальные скоупы для изоляции данных.
         */
        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                if (empty($model->correlation_id)) {
                    $model->correlation_id = request()->header('X-Correlation-ID') ?? strval(fake()->uuid());
                }
            });

            static::addGlobalScope('tenant_scoping', function ($builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', tenant('id'));
                }
            });
        }

        /**
         * Отношения (Relationships)
         */
        public function masters(): HasMany
        {
            return $this->hasMany(Master::class, 'salon_id');
        }

        public function services(): HasMany
        {
            return $this->hasMany(BeautyService::class, 'salon_id');
        }

        public function appointments(): HasMany
        {
            return $this->hasMany(Appointment::class, 'salon_id');
        }

        public function consumables(): HasMany
        {
            return $this->hasMany(BeautyConsumable::class, 'salon_id');
        }

        public function products(): HasMany
        {
            return $this->hasMany(BeautyProduct::class, 'salon_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(Review::class, 'salon_id');
        }

        public function tenant(): BelongsTo
        {
            return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
        }

        public function businessGroup(): BelongsTo
        {
            return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
        }
}
