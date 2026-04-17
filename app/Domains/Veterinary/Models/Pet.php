<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class Pet extends Model
{


        protected $table = 'pets';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'owner_id',
            'name',
            'species',
            'breed',
            'birth_date',
            'gender',
            'weight',
            'medical_notes',
            'vaccination_history',
            'chip_number',
            'chip_installed_at',
            'passport_number',
            'is_neutered',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'birth_date' => 'date',
            'chip_installed_at' => 'date',
            'is_neutered' => 'boolean',
            'weight' => 'float',
            'vaccination_history' => 'json',
            'tags' => 'json',
        ];

        /**
         * Boot logic
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scope', function (Builder $builder) {
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $builder->where('pets.tenant_id', tenant()->id);
                }
            });

            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $model->tenant_id = $model->tenant_id ?? tenant()->id;
                }
            });
        }

        /**
         * Relations: Owner (User)
         */
        public function owner(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'owner_id');
        }

        /**
         * Relations: Appointments
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(VeterinaryAppointment::class, 'pet_id');
        }

        /**
         * Relations: Medical Records (The Full History)
         */
        public function medicalRecords(): HasMany
        {
            return $this->hasMany(MedicalRecord::class, 'pet_id');
        }

        /**
         * Passport: Vaccinations
         */
        public function vaccinations(): HasMany
        {
            return $this->hasMany(PetVaccination::class, 'pet_id');
        }

        /**
         * Passport: Pedigree
         */
        public function pedigree(): \Illuminate\Database\Eloquent\Relations\HasOne
        {
            return $this->hasOne(PetPedigree::class, 'pet_id');
        }

        /**
         * Biometrics: Metrics History
         */
        public function metrics(): HasMany
        {
            return $this->hasMany(PetMetric::class, 'pet_id');
        }

        /**
         * Calculate Age
         */
        public function getAgeAttribute(): ?int
        {
            return $this->birth_date ? $this->birth_date->age : null;
        }
}
