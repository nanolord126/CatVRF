<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class LabTestModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'dental_lab_tests';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'lab_test_type_id',
        'tenant_id',
        'barcode',
        'status',
        'sample_collected_at',
        'completed_at',
        'cost',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'sample_collected_at' => 'datetime',
        'completed_at' => 'datetime',
        'cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }

    public function labTestType(): BelongsTo
    {
        return $this->belongsTo(LabTestTypeModel::class, 'lab_test_type_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResultModel::class, 'lab_test_id');
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode);
    }
}
