<?php

declare(strict_types=1);

namespace App\Domains\Medical\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class MedicalTestOrder extends Model
{
    use TenantScoped;

    protected $table = 'medical_test_orders';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'appointment_id',
        'patient_id',
        'clinic_id',
        'test_order_number',
        'tests',
        'total_amount',
        'commission_amount',
        'status',
        'payment_status',
        'ordered_at',
        'completed_at',
        'results',
        'transaction_id',
        'correlation_id',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'tests' => 'collection',
        'results' => 'collection',
        'total_amount' => 'float',
        'commission_amount' => 'float',
        'ordered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(MedicalAppointment::class, 'appointment_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(MedicalClinic::class, 'clinic_id');
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if ($tenantId = $this->guard?->user()?->tenant_id ?? filament()?->getTenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }
}
