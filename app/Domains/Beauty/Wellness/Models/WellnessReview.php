<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WellnessReview extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'wellness_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'center_id',
            'specialist_id',
            'client_id',
            'appointment_id', // linked to a specific visit
            'rating', // 1-5 integer
            'comment',
            'photos_json',
            'is_verified', // verified via verified purchase/visit
            'correlation_id',
        ];

        protected $casts = [
            'rating' => 'integer',
            'photos_json' => 'json',
            'is_verified' => 'boolean',
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
         * Relation with the specialist.
         */
        public function specialist(): BelongsTo
        {
            return $this->belongsTo(WellnessSpecialist::class, 'specialist_id');
        }

        /**
         * Relation with the specific appointment.
         */
        public function appointment(): BelongsTo
        {
            return $this->belongsTo(WellnessAppointment::class, 'appointment_id');
        }

        /**
         * Verified reviews filter.
         */
        public function scopeVerified(Builder $query): Builder
        {
            return $query->where('is_verified', true);
        }

        /**
         * Positive reviews filter (4+).
         */
        public function scopePositive(Builder $query): Builder
        {
            return $query->where('rating', '>=', 4);
        }

        /**
         * Negative reviews filter (<3).
         */
        public function scopeNegative(Builder $query): Builder
        {
            return $query->where('rating', '<', 3);
        }
}
