<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;
use App\Models\User;

final class MedicalPrescription extends Model
{
    use TenantScoped;

    protected $table = 'medical_prescriptions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'record_id',
        'patient_id',
        'doctor_id',
        'medications',
        'valid_until',
        'is_digital_signed',
        'correlation_id',
    ];

    protected $hidden = ['deleted_at', 'correlation_id'];

    protected $casts = [
        'medications' => 'array',
        'valid_until' => 'datetime',
        'is_digital_signed' => 'boolean',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(MedicalAppointment::class, 'appointment_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(MedicalDoctor::class, 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 0);
        });
    }
}
