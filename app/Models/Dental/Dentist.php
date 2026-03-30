<?php declare(strict_types=1);

namespace App\Models\Dental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Dentist extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'dentists';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'full_name',
            'specialization',
            'experience_years',
            'bio',
            'certifications',
            'rating',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'certifications' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'rating' => 'integer',
            'experience_years' => 'integer',
            'tenant_id' => 'integer',
        ];

        /**
         * Boot logic for automatic UUID and tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());

                if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Relations: Clinic the dentist is associated with.
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(DentalClinic::class, 'clinic_id');
        }

        /**
         * Relations: Appointments for this dentist.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(DentalAppointment::class, 'dentist_id');
        }

        /**
         * Relations: Reviews for the dentist.
         */
        public function reviews(): HasMany
        {
            return $this->hasMany(DentalReview::class, 'dentist_id');
        }

        /**
         * Relations: Treatment plans managed by this dentist.
         */
        public function treatmentPlans(): HasMany
        {
            return $this->hasMany(DentalTreatmentPlan::class, 'dentist_id');
        }

        /**
         * Calculate professional rank based on experience and rating.
         */
        public function getRankAttribute(): string
        {
            $baseRank = match (true) {
                $this->experience_years < 3 => 'Junior Specialist',
                $this->experience_years < 10 => 'Senior Dentist',
                default => 'Master Dentist / Consultant',
            };

            if ($this->rating >= 95) {
                return 'Highly Rated ' . $baseRank;
            }

            return $baseRank;
        }

        /**
         * Medical Data Privacy Check (ФЗ-152).
         */
        public function checkPrivacyScope(int $tenantId): bool
        {
            return $this->tenant_id === $tenantId;
        }
}
