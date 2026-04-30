<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ManagerKPIHistory — История изменений KPI менеджера
 * 
 * Хранит исторические данные для отслеживания прогресса KPI во времени
 */
final class ManagerKPIHistory extends Model
{
    use HasFactory;

    protected $table = 'crm_manager_kpi_history';

    protected $fillable = [
        'kpi_id',
        'score',
        'actuals',
        'notes',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'actuals' => 'json',
    ];

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * Parent KPI record
     */
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(ManagerKPI::class);
    }
}
