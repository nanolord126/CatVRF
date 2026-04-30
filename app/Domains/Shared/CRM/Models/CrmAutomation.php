<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Database\Factories\CRM\CrmAutomationFactory;

/**
 * CRM Automation — триггерная кампания маркетинга.
 * Автоматические действия при наступлении событий (день рождения, неактивность и т.д.).
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmAutomation extends Model
{
    use TenantScoped;

    protected $table = 'crm_automations';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'correlation_id',
        'tags',
        'name',
        'description',
        'vertical',
        'is_active',
        'trigger_type',
        'trigger_config',
        'action_type',
        'action_config',
        'delay_type',
        'delay_minutes',
        'total_sent',
        'total_opened',
        'total_clicked',
        'total_converted',
    ];

    protected $casts = [
        'tags' => 'json',
        'trigger_config' => 'json',
        'action_config' => 'json',
        'is_active' => 'boolean',
        'delay_minutes' => 'integer',
        'total_sent' => 'integer',
        'total_opened' => 'integer',
        'total_clicked' => 'integer',
        'total_converted' => 'integer',
    ];

    /**
     * Коэффициент конверсии (%).
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->total_sent === 0) {
            return 0.0;
        }

        return round(($this->total_converted / $this->total_sent) * 100, 2);
    }

    /**
     * Коэффициент открытий (%).
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->total_sent === 0) {
            return 0.0;
        }

        return round(($this->total_opened / $this->total_sent) * 100, 2);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CrmAutomationLog::class, 'crm_automation_id');
    }

    protected static function newFactory(): CrmAutomationFactory
    {
        return CrmAutomationFactory::new();
    }

    protected static function booted(): void
    {
        self::creating(function (self $model): void {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
