<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalAppointment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'medical_appointments';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'doctor_id',
            'patient_id',
            'service_id',
            'appointment_number',
            'starts_at',
            'ends_at',
            'completed_at',
            'cancelled_at',
            'status',
            'payment_status',
            'total_amount_kopecks',
            'client_comment',
            'internal_notes',
            'transaction_id',
            'correlation_id',
        ];

        protected $hidden = ['deleted_at', 'correlation_id'];

        protected $casts = [
            'total_amount_kopecks' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('tenant_id', tenant()->id ?? 0);
            });
        }

        public function clinic(): BelongsTo
        {
            return $this->belongsTo(MedicalClinic::class, 'clinic_id');
        }

        public function doctor(): BelongsTo
        {
            return $this->belongsTo(MedicalDoctor::class, 'doctor_id');
        }

        public function patient(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'patient_id');
        }

        public function service(): BelongsTo
        {
            return $this->belongsTo(MedicalService::class, 'service_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(MedicalReview::class, 'appointment_id');
        }

        public function prescriptions(): HasMany
        {
            return $this->hasMany(MedicalPrescription::class, 'appointment_id');
        }

        public function testOrders(): HasMany
        {
            return $this->hasMany(MedicalTestOrder::class, 'appointment_id');
        }
}
