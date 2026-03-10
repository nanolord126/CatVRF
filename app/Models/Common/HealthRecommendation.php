<?php

namespace App\Models\Common;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\MedicalCard;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecommendation extends Model
{
    use StrictTenantIsolation, HasEcosystemTracing;
    protected $table = 'user_health_recommendations';

    protected $fillable = [
        'user_id', 'target_type', 'target_id', 'title', 'description', 
        'frequency', 'next_due_date', 'is_completed', 'history_log', 
        'medical_card_id', 'correlation_id'
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'is_completed' => 'boolean',
        'history_log' => 'array',
    ];

    /** Показать кто владеет рекомендацией */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** С какой медкартой связана (источник) */
    public function medicalCard(): BelongsTo
    {
        return $this->belongsTo(MedicalCard::class, 'medical_card_id');
    }

    /** Завершение текущего цикла */
    public function completeRecommendation()
    {
        $log = $this->history_log ?? [];
        $log[] = now()->toDateTimeString();

        $nextDate = match($this->frequency) {
            'DAILY' => $this->next_due_date->addDay(),
            'WEEKLY' => $this->next_due_date->addWeek(),
            'MONTHLY' => $this->next_due_date->addMonth(),
            'YEARLY' => $this->next_due_date->addYear(),
            default => null
        };

        $this->update([
            'history_log' => $log,
            'is_completed' => ($nextDate === null),
            'next_due_date' => $nextDate ?? $this->next_due_date,
        ]);
    }
}








