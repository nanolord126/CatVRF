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
        'tenant_id',
        'appointment_id',
        'doctor_id',
        'patient_id',
        'prescription_number',
        'medications',
        'notes',
        'issued_at',
        'expires_at',
        'status',
        'correlation_id',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'medications' => 'collection',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = auth()?->user()?->tenant_id ?? filament()?->getTenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
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
