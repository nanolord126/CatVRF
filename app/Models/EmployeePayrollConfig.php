<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayrollConfig extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = [
        'user_id',
        'base_salary',
        'commission_rate', // e.g. 10.00 for 10%
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}









