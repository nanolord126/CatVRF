<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class VeterinaryAppointment extends Model
{

    protected $table = 'veterinary_appointments';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'veterinarian_id',
            'pet_id',
            'service_id',
            'client_id',
            'appointment_at',
            'status',
            'final_price',
            'payment_status',
            'symptoms',
            'cancellation_reason',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'appointment_at' => 'datetime',
            'final_price' => 'integer',
            'tags' => 'json',
        ];

        /**
         * Boot logic
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant_scope', function (Builder $builder) {
                if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                    $builder->where('veterinary_appointments.tenant_id', tenant()->id);
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
         * Relations: Clinic
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(VeterinaryClinic::class, 'clinic_id');
        }

        /**
         * Relations: Veterinarian
         */
        public function veterinarian(): BelongsTo
        {
            return $this->belongsTo(Veterinarian::class, 'veterinarian_id');
        }

        /**
         * Relations: Pet
         */
        public function pet(): BelongsTo
        {
            return $this->belongsTo(Pet::class, 'pet_id');
        }

        /**
         * Relations: User (Pet Owner / Client)
         */
        public function client(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }

        /**
         * Relations: Specific Service
         */
        public function service(): BelongsTo
        {
            return $this->belongsTo(VeterinaryService::class, 'service_id');
        }

        /**
         * Relations: Resulting Medical Record
         */
        public function medicalRecord(): BelongsTo
        {
            return $this->hasOne(MedicalRecord::class, 'appointment_id');
        }
}
