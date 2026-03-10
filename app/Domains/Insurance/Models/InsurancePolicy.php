<?php

namespace App\Domains\Insurance\Models;

use App\Traits\HasAuditLog;
use App\Traits\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurancePolicy extends Model
{
    use HasAuditLog, HasEcosystemFeatures;

    protected $fillable = [
        'tenant_id', 'policyholder_id', 'policy_number', 'type',
        'status', 'premium_amount', 'coverage_amount', 'start_date', 'end_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function policyholder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'policyholder_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
