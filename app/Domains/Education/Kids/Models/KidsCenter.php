<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsCenter extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'kids_centers';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'store_id',
            'name',
            'center_type', // playground, education, club, day_care
            'address',
            'geo_point',
            'capacity_limit',
            'hourly_rate',
            'is_safety_verified',
            'facility_details',
            'schedule_hours',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'hourly_rate' => 'integer', // Kopecks (Canon 2026)
            'is_safety_verified' => 'boolean',
            'facility_details' => 'json', // pool, cafe, lockers, cameras, parking
            'schedule_hours' => 'json', // mon-sun: open/close
            'tags' => 'json',
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
         * Store relationship.
         */
        public function store(): BelongsTo
        {
            return $this->belongsTo(KidsStore::class, 'store_id');
        }

        /**
         * Scheduled events at this center.
         */
        public function events(): HasMany
        {
            return $this->hasMany(KidsEvent::class, 'center_id');
        }

        /**
         * Safety filter.
         */
        public function scopeVerified(Builder $query): Builder
        {
            return $query->where('is_safety_verified', true);
        }

        /**
         * Filter by facility tags.
         */
        public function scopeWithPool(Builder $query): Builder
        {
            return $query->whereJsonContains('facility_details', 'pool');
        }

        /**
         * Hourly rate range filter.
         */
        public function scopeUnderRate(Builder $query, int $maxRate): Builder
        {
            return $query->where('hourly_rate', '<=', $maxRate);
        }

        /**
         * Check if center is currently open.
         */
        public function isOpenNow(): bool
        {
            $day = strtolower(date('l'));
            $schedule = $this->schedule_hours[$day] ?? null;
            if (!$schedule) return false;

            $now = date('H:i');
            return $now >= $schedule['open'] && $now <= $schedule['close'];
        }

        /**
         * Formatted hourly rate display helper.
         */
        public function getFormattedRateAttribute(): string
        {
            return number_format($this->hourly_rate / 100, 2, '.', ' ') . ' RUB/hr';
        }
}
