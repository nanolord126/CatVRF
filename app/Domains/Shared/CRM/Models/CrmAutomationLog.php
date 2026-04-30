<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * CRM Automation Log — журнал выполнения автоматизаций.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmAutomationLog extends Model
{
    use TenantScoped;

    protected $table = 'crm_automation_logs';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'crm_automation_id',
        'crm_client_id',
        'correlation_id',
        'status',
        'result_data',
        'error_message',
        'executed_at',
    ];

    protected $casts = [
        'result_data' => 'json',
        'executed_at' => 'datetime',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(CrmAutomation::class, 'crm_automation_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', static function ($query) {
            if (app()->has('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
