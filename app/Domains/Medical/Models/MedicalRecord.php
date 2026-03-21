<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MedicalRecord extends Model
{
    use SoftDeletes;

    protected $table = 'medical_records';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'clinic_id',
        'doctor_id',
        'record_type',
        'diagnosis',
        'treatment',
        'test_results',
        'medications',
        'attachments',
        'recorded_at',
        'is_confidential',
        'correlation_id',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'test_results' => 'collection',
        'medications' => 'collection',
        'attachments' => 'collection',
        'recorded_at' => 'datetime',
        'is_confidential' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = auth()?->user()?->tenant_id ?? filament()?->getTenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'patient_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(MedicalClinic::class, 'clinic_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(MedicalDoctor::class, 'doctor_id');
    }
}
