<?php declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WellnessProgram extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'wellness_programs';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'center_id',
            'client_id',
            'specialist_id',
            'name',
            'program_data', // Detailed nutrition, exercises, routine (JSON)
            'medical_restrictions', // Safety checks
            'health_goal', // weight_loss, muscle_gain, recovery
            'start_at',
            'end_at',
            'correlation_id',
        ];

        protected $casts = [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'program_data' => 'json',
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
         * Relation with the specialist (coach/doctor).
         */
        public function specialist(): BelongsTo
        {
            return $this->belongsTo(WellnessSpecialist::class, 'specialist_id');
        }

        /**
         * Active programs filter.
         */
        public function scopeActive(Builder $query): Builder
        {
           return $query->where('end_at', '>', now());
        }

        /**
         * Recovery programs filter.
         */
        public function scopeRecovery(Builder $query): Builder
        {
            return $query->where('health_goal', 'recovery');
        }

        /**
         * Weight loss programs filter.
         */
        public function scopeWeightLoss(Builder $query): Builder
        {
            return $query->where('health_goal', 'weight_loss');
        }
}
