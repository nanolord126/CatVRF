<?php declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WellnessCenter extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'wellness_centers';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'type',
            'address',
            'geo_point',
            'schedule_json',
            'rating',
            'review_count',
            'is_active',
            'is_verified',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'geo_point' => 'json',
            'schedule_json' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'rating' => 'decimal:2',
        ];

        /**
         * Boot the model to handle automatic UUID and scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (string) tenant()->id;
                $model->correlation_id = $model->correlation_id ?? (string) request()->header('X-Correlation-ID', Str::uuid());
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Relations with other domain entities.
         */
        public function specialists(): HasMany
        {
            return $this->hasMany(WellnessSpecialist::class, 'center_id');
        }

        public function services(): HasMany
        {
            return $this->hasMany(WellnessService::class, 'center_id');
        }

        public function memberships(): HasMany
        {
            return $this->hasMany(WellnessMembership::class, 'center_id');
        }

        public function appointments(): HasMany
        {
            return $this->hasMany(WellnessAppointment::class, 'center_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(WellnessReview::class, 'center_id');
        }

        /**
         * Filter for type of center.
         */
        public function scopeOfType(Builder $query, string $type): Builder
        {
            return $query->where('type', $type);
        }

        /**
         * Filter for verified centers.
         */
        public function scopeVerified(Builder $query): Builder
        {
            return $query->where('is_verified', true);
        }
}
