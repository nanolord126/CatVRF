<?php

namespace App\Models\HR;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\BaseTenantModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HRExchangeReview extends BaseTenantModel
{
    protected $table = 'hr_exchange_reviews';

    protected $fillable = [
        'hr_exchange_task_id', 'response_id', 'reviewer_id', 
        'employee_id', 'rating', 'comment', 'ai_tags'
    ];

    protected $casts = [
        'ai_tags' => 'array',
    ];

    /** Кто отпустил сотрудника на смену (Заказчик) */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** Сам сотрудник (Исполнитель) */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /** Сама задача на бирже */
    public function task(): BelongsTo
    {
        return $this->belongsTo(HRExchangeTask::class, 'hr_exchange_task_id');
    }

    /** Сам отклик */
    public function response(): BelongsTo
    {
        return $this->belongsTo(HRExchangeResponse::class, 'response_id');
    }
}








