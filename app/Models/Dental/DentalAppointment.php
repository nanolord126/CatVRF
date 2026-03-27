<?php

declare(strict_types=1);

namespace App\Models\Dental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Model for Dental Appointment.
 * Strictly follows CANON 2026: Medical Privacy (ФЗ-152) and Fraud Control.
 */
final class DentalAppointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dental_appointments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'dentist_id',
        'client_id',
        'scheduled_at',
        'status',
        'total_price',
        'is_prepaid',
        'internal_notes',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'tags' => 'json',
        'total_price' => 'integer',
        'is_prepaid' => 'boolean',
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
     * Relations: Clinic the appointment is at.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(DentalClinic::class, 'clinic_id');
    }

    /**
     * Relations: Dentist performing the service.
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class, 'dentist_id');
    }

    /**
     * Relations: Service performed (if applicable to one service).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(DentalService::class, 'service_id');
    }

    /**
     * Check if the appointment is for a new patient.
     * New patient appointments often have different fraud scoring.
     */
    public function isNewPatient(): bool
    {
        return self::where('client_id', $this->client_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'completed')
            ->count() === 0;
    }

    /**
     * Health Privacy Guard (ФЗ-152).
     * Sensitive notes should not be exposed by default.
     */
    public function getObfuscatedNotes(): string
    {
        if (request()->user()?->can('view_medical_notes', $this)) {
            return $this->internal_notes ?? '';
        }

        return '[HIDDEN - MEDICAL CONFIDENTIALITY ФЗ-152]';
    }

    /**
     * Change status with auditing.
     */
    public function transitionTo(string $newStatus): void
    {
        $oldStatus = $this->status;
        $this->update(['status' => $newStatus]);

        \Illuminate\Support\Facades\Log::channel('audit')->info('Appointment status changed', [
            'appointment_id' => $this->id,
            'old' => $oldStatus,
            'new' => $newStatus,
            'correlation_id' => $this->correlation_id,
        ]);
    }
}
