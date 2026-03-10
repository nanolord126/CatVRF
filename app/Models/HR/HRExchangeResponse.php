<?php

namespace App\Models\HR;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\BaseTenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HRExchangeResponse extends BaseTenantModel
{
    protected $table = 'hr_exchange_responses';

    protected $fillable = [
        'hr_exchange_task_id', 'employee_id', 
        'current_tenant_id', 'status'
    ];

    /** Сама задача на бирже */
    public function task(): BelongsTo
    {
        return $this->belongsTo(HRExchangeTask::class, 'hr_exchange_task_id');
    }

    /** Сотрудник, решивший подзаработать (респонс на EXCHANGE) */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}








