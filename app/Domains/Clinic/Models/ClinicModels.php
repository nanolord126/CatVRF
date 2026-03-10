<?php

namespace App\Domains\Clinic\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use App\Traits\Common\HasEcosystemMedia;
use App\Contracts\Common\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;

class Patient extends Model implements AIEnableEcosystemEntity, HasMedia
{
    use HasEcosystemFeatures, HasEcosystemAuth, HasEcosystemMedia, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'medical_history_summary' => 'array',
        'correlation_id' => 'string',
        'tenant_id' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (Patient $model) {
            $model->correlation_id ??= Str::uuid();
            $model->tenant_id ??= Auth::guard('tenant')->id();
            
            Log::channel('clinic')->info('Patient creating', [
                'correlation_id' => $model->correlation_id,
                'user_id' => $model->user_id,
                'tenant_id' => $model->tenant_id,
            ]);
        });

        static::created(function (Patient $model) {
            AuditLog::create([
                'entity_type' => Patient::class,
                'entity_id' => $model->id,
                'action' => 'created',
                'user_id' => Auth::id(),
                'tenant_id' => $model->tenant_id,
                'correlation_id' => $model->correlation_id,
                'changes' => $model->getAttributes(),
            ]);

            Log::channel('clinic')->info('Patient created', [
                'correlation_id' => $model->correlation_id,
                'patient_id' => $model->id,
            ]);
        });

        static::updating(function (Patient $model) {
            $model->correlation_id ??= Str::uuid();
            
            Log::channel('clinic')->info('Patient updating', [
                'correlation_id' => $model->correlation_id,
                'patient_id' => $model->id,
            ]);
        });

        static::updated(function (Patient $model) {
            AuditLog::create([
                'entity_type' => Patient::class,
                'entity_id' => $model->id,
                'action' => 'updated',
                'user_id' => Auth::id(),
                'tenant_id' => $model->tenant_id,
                'correlation_id' => $model->correlation_id,
                'changes' => $model->getChanges(),
            ]);

            Log::channel('clinic')->info('Patient updated', [
                'correlation_id' => $model->correlation_id,
                'patient_id' => $model->id,
            ]);
        });

        static::deleting(function (Patient $model) {
            if ($model->appointments()->exists()) {
                Log::channel('clinic')->warning('Patient deletion blocked - has appointments', [
                    'correlation_id' => $model->correlation_id,
                    'patient_id' => $model->id,
                ]);
            }
        });
    }

    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        // Стационарные услуги - фиксированная цена без корректировок
        return max(0, $basePrice);
    }

    public function getTrustScore(): int
    {
        return 100;
    }

    public function generateAiChecklist(): array
    {
        return [
            'Vitals check' => ['description' => 'Check vital signs'],
            'Medication history update' => ['description' => 'Update current medications'],
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ClinicAppointment::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }
}

class ClinicAppointment extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'appointment_at' => 'datetime',
        'is_paid' => 'boolean',
        'notes' => 'array',
        'correlation_id' => 'string',
        'tenant_id' => 'string',
        'status' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicAppointment $model) {
            $model->correlation_id ??= Str::uuid();
            $model->tenant_id ??= Auth::guard('tenant')->id();
            $model->status ??= 'scheduled';

            Log::channel('clinic')->info('Appointment creating', [
                'correlation_id' => $model->correlation_id,
                'patient_id' => $model->patient_id,
                'appointment_at' => $model->appointment_at,
            ]);
        });

        static::created(function (ClinicAppointment $model) {
            AuditLog::create([
                'entity_type' => ClinicAppointment::class,
                'entity_id' => $model->id,
                'action' => 'created',
                'user_id' => Auth::id(),
                'tenant_id' => $model->tenant_id,
                'correlation_id' => $model->correlation_id,
                'changes' => $model->getAttributes(),
                'metadata' => [
                    'patient_id' => $model->patient_id,
                    'doctor_id' => $model->doctor_id,
                    'appointment_at' => $model->appointment_at?->toIso8601String(),
                ],
            ]);

            Log::channel('clinic')->info('Appointment created', [
                'correlation_id' => $model->correlation_id,
                'appointment_id' => $model->id,
            ]);
        });

        static::updating(function (ClinicAppointment $model) {
            $model->correlation_id ??= Str::uuid();
            
            Log::channel('clinic')->info('Appointment updating', [
                'correlation_id' => $model->correlation_id,
                'appointment_id' => $model->id,
            ]);
        });

        static::updated(function (ClinicAppointment $model) {
            AuditLog::create([
                'entity_type' => ClinicAppointment::class,
                'entity_id' => $model->id,
                'action' => 'updated',
                'user_id' => Auth::id(),
                'tenant_id' => $model->tenant_id,
                'correlation_id' => $model->correlation_id,
                'changes' => $model->getChanges(),
                'metadata' => [
                    'status' => $model->status,
                    'is_paid' => $model->is_paid,
                ],
            ]);

            Log::channel('clinic')->info('Appointment updated', [
                'correlation_id' => $model->correlation_id,
                'appointment_id' => $model->id,
            ]);
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class);
    }
}

class MedicalRecord extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'diagnosis_data' => 'array',
        'prescriptions' => 'array',
        'is_finalized' => 'boolean',
        'correlation_id' => 'string',
        'tenant_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MedicalRecord $model) {
            $model->correlation_id ??= Str::uuid();
            $model->tenant_id ??= Auth::guard('tenant')->id();
            $model->is_finalized ??= false;

            if (empty($model->diagnosis_data)) {
                $model->diagnosis_data = [];
            }
            if (empty($model->prescriptions)) {
                $model->prescriptions = [];
            }

            Log::channel('clinic')->info('Medical record creating', [
                'correlation_id' => $model->correlation_id,
                'patient_id' => $model->patient_id,
            ]);
        });

        static::created(function (MedicalRecord $model) {
            AuditLog::create([
                'entity_type' => MedicalRecord::class,
                'entity_id' => $model->id,
                'action' => 'created',
                'user_id' => Auth::id(),
                'tenant_id' => $model->tenant_id,
                'correlation_id' => $model->correlation_id,
                'changes' => $model->getAttributes(),
                'metadata' => [
                    'patient_id' => $model->patient_id,
                    'appointment_id' => $model->appointment_id,
                ],
            ]);

            Log::channel('clinic')->info('Medical record created', [
                'correlation_id' => $model->correlation_id,
                'record_id' => $model->id,
            ]);
        });

        static::updating(function (MedicalRecord $model) {
            $model->correlation_id ??= Str::uuid();
            
            Log::channel('clinic')->info('Medical record updating', [
                'correlation_id' => $model->correlation_id,
                'record_id' => $model->id,
            ]);
        });

        static::updated(function (MedicalRecord $model) {
            AuditLog::create([
                'entity_type' => MedicalRecord::class,
                'entity_id' => $model->id,
                'action' => 'updated',
                'user_id' => Auth::id(),
                'tenant_id' => $model->tenant_id,
                'correlation_id' => $model->correlation_id,
                'changes' => $model->getChanges(),
                'metadata' => [
                    'is_finalized' => $model->is_finalized,
                    'prescription_count' => count($model->prescriptions ?? []),
                ],
            ]);

            Log::channel('clinic')->info('Medical record updated', [
                'correlation_id' => $model->correlation_id,
                'record_id' => $model->id,
            ]);
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(ClinicAppointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }
}

class ClinicService extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_available' => 'boolean',
        'correlation_id' => 'string',
        'tenant_id' => 'string',
        'attributes' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicService $model) {
            $model->correlation_id ??= Str::uuid();
            $model->tenant_id ??= Auth::guard('tenant')->id();
            $model->is_available ??= true;

            if ($model->price === null || $model->price <= 0) {
                throw new \InvalidArgumentException('Service price must be greater than 0');
            }

            if ($model->duration_minutes === null || $model->duration_minutes <= 0) {
                throw new \InvalidArgumentException('Service duration must be greater than 0');
            }

            Log::channel('clinic')->info('Service creating', [
                'correlation_id' => $model->correlation_id,
                'name' => $model->name ?? 'Unknown',
                'price' => $model->price,
            ]);
        });

        static::created(function (ClinicService $model) {
            AuditLog::create([
                'entity_type' => ClinicService::class,
                'entity_id' => $model->id,
                'action' => 'created',
                'user_id' => Auth::id(),
                'tenant_id' => $model->tenant_id,
                'correlation_id' => $model->correlation_id,
                'changes' => $model->getAttributes(),
                'metadata' => [
                    'price' => (float) $model->price,
                    'duration_minutes' => $model->duration_minutes,
                ],
            ]);

            Log::channel('clinic')->info('Service created', [
                'correlation_id' => $model->correlation_id,
                'service_id' => $model->id,
            ]);
        });

        static::updating(function (ClinicService $model) {
            $model->correlation_id ??= Str::uuid();

            if ($model->isDirty('price') && $model->price <= 0) {
                throw new \InvalidArgumentException('Service price must be greater than 0');
            }

            if ($model->isDirty('duration_minutes') && $model->duration_minutes <= 0) {
                throw new \InvalidArgumentException('Service duration must be greater than 0');
            }

            Log::channel('clinic')->info('Service updating', [
                'correlation_id' => $model->correlation_id,
                'service_id' => $model->id,
            ]);
        });

        static::updated(function (ClinicService $model) {
            AuditLog::create([
                'entity_type' => ClinicService::class,
                'entity_id' => $model->id,
                'action' => 'updated',
                'user_id' => Auth::id(),
                'tenant_id' => $model->tenant_id,
                'correlation_id' => $model->correlation_id,
                'changes' => $model->getChanges(),
                'metadata' => [
                    'is_available' => $model->is_available,
                    'price_changed' => $model->isDirty('price'),
                ],
            ]);

            Log::channel('clinic')->info('Service updated', [
                'correlation_id' => $model->correlation_id,
                'service_id' => $model->id,
            ]);
        });
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ClinicAppointment::class);
    }
}
