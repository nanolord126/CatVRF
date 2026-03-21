<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MedicalAppointment extends Model
{
    use SoftDeletes;

    protected $table = 'medical_appointments';

    protected $fillable = [
        'tenant_id',
        'clinic_id',
        'doctor_id',
        'patient_id',
        'service_id',
        'appointment_number',
        'scheduled_at',
        'completed_at',
        'cancelled_at',
        'status',
        'payment_status',
        'price',
        'commission_amount',
        'notes',
        'diagnosis',
        'prescription',
        'transaction_id',
        'correlation_id',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'diagnosis' => 'collection',
        'prescription' => 'collection',
        'price' => 'float',
        'commission_amount' => 'float',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = auth()?->user()?->tenant_id ?? filament()?->getTenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
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
