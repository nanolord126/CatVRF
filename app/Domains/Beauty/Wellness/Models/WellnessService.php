<?php declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WellnessService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'wellness_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'center_id',
            'specialist_id',
            'name',
            'description',
            'price',
            'duration_minutes',
            'consumables',
            'medical_restrictions',
            'correlation_id',
        ];

        protected $casts = [
            'price' => 'integer', // Stored in kopecks according to Canon 2026
            'duration_minutes' => 'integer',
            'consumables' => 'json',
            'medical_restrictions' => 'json',
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
         * Relation with the specialist providing the service.
         */
        public function specialist(): BelongsTo
        {
            return $this->belongsTo(WellnessSpecialist::class, 'specialist_id');
        }

        /**
         * Appointment history for this service.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(WellnessAppointment::class, 'service_id');
        }

        /**
         * Subscriptions/Memberships using this service.
         */
        public function memberships(): HasMany
        {
            return $this->hasMany(WellnessMembership::class, 'service_id');
        }

        /**
         * Filter services by price range.
         */
        public function scopePriceRange(Builder $query, int $min, int $max): Builder
        {
            return $query->whereBetween('price', [$min, $max]);
        }

        /**
         * Filter services by duration.
         */
        public function scopeDurationUnder(Builder $query, int $minutes): Builder
        {
            return $query->where('duration_minutes', '<=', $minutes);
        }
}
