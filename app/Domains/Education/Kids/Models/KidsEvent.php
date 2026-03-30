<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsEvent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'kids_events';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'center_id',
            'name',
            'event_type', // workshop, party, show, education, competition
            'description',
            'scheduled_at',
            'duration_minutes',
            'price_per_child',
            'max_children',
            'min_age_months',
            'max_age_months',
            'instructor_name',
            'is_public_holiday',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'price_per_child' => 'integer', // Kopecks (Canon 2026)
            'max_children' => 'integer',
            'min_age_months' => 'integer',
            'max_age_months' => 'integer',
            'is_public_holiday' => 'boolean',
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
         * Center relationship.
         */
        public function center(): BelongsTo
        {
            return $this->belongsTo(KidsCenter::class, 'center_id');
        }

        /**
         * Upcoming activities.
         */
        public function scopeUpcoming(Builder $query): Builder
        {
            return $query->where('scheduled_at', '>', now());
        }

        /**
         * Events for a specific age.
         */
        public function scopeForAge(Builder $query, int $months): Builder
        {
            return $query->where('min_age_months', '<=', $months)
                ->where('max_age_months', '>=', $months);
        }

        /**
         * Workshop activities.
         */
        public function scopeWorkshops(Builder $query): Builder
        {
            return $query->where('event_type', 'workshop');
        }

        /**
         * Get remaining slots.
         */
        public function getAvailableSlots(int $bookedCount): int
        {
            return max(0, $this->max_children - $bookedCount);
        }

        /**
         * Formatted price display helper.
         */
        public function getFormattedPriceAttribute(): string
        {
            return number_format($this->price_per_child / 100, 2, '.', ' ') . ' RUB';
        }

        /**
         * Display age range label.
         */
        public function getAgeRangeLabelAttribute(): string
        {
            $min = floor($this->min_age_months / 12);
            $max = floor($this->max_age_months / 12);
            return sprintf('%g – %g years', $min, $max);
        }
}
