<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MedicalPrescription extends Model
{
    use SoftDeletes;

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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 0);
        });
    }

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
        return $this->belongsTo(\App\Models\User::class, 'patient_id');
    }
}
