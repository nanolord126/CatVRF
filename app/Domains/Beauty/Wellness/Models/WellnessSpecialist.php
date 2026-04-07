<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WellnessSpecialist extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'wellness_specialists';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'center_id',
            'full_name',
            'qualifications',
            'experience_years',
            'specialization',
            'rating',
            'medical_compliance',
            'correlation_id',
        ];

        protected $casts = [
            'qualifications' => 'json',
            'medical_compliance' => 'json',
            'rating' => 'decimal:2',
        ];

        /**
         * Boot the model with tenant scoping and record automation.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (string) (tenant()->id ?? 'null');
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Relation with the wellness center.
         */
        public function center(): BelongsTo
        {
            return $this->belongsTo(WellnessCenter::class, 'center_id');
        }

        /**
         * Specialist appointments history.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(WellnessAppointment::class, 'specialist_id');
        }

        /**
         * Services offered by this specialist.
         */
        public function services(): HasMany
        {
            return $this->hasMany(WellnessService::class, 'specialist_id');
        }

        /**
         * Filter specialists by specialization.
         */
        public function scopeOfSpecialization(Builder $query, string $specialization): Builder
        {
            return $query->where('specialization', $specialization);
        }

        /**
         * High rated specialists filter.
         */
        public function scopeHighlyRated(Builder $query): Builder
        {
            return $query->where('rating', '>=', 4.5);
        }
}
