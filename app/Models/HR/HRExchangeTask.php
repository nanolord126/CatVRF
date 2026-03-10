<?php

namespace App\Models\HR;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\BaseTenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HRExchangeTask extends BaseTenantModel
{
    protected $table = 'hr_exchange_tasks';

    protected $fillable = [
        'tenant_id', 'title', 'description', 'category', 
        'reward_amount', 'start_at', 'end_at', 'slots_available', 
        'status', 'correlation_id'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'reward_amount' => 'decimal:2',
    ];

    /** Тенант, создавший запрос на бирже */
    // Примечание: в schema-per-tenant запросы могут дублироваться в центральную таблицу
    // или фильтроваться по домену. Здесь упрощенная модель для демонстрации.

    /** Кто откликнулся на задачу */
    public function responses(): HasMany
    {
        return $this->hasMany(HRExchangeResponse::class, 'hr_exchange_task_id');
    }
}








