<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * CrmEventProfile — CRM-профиль клиента вертикали Мероприятия/Свадьбы.
 *
 * Предстоящие мероприятия, бюджеты, подрядчики, важные даты.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmEventProfile extends Model
{
    use TenantScoped;

    protected $table = 'crm_event_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'upcoming_events', 'past_events',
        'preferred_venues', 'preferred_caterers', 'preferred_decorators',
        'preferred_photographers', 'event_style', 'typical_budget',
        'typical_guest_count', 'vendor_contacts', 'important_dates',
        'is_event_planner', 'checklist_template', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'upcoming_events' => 'json',
        'past_events' => 'json',
        'preferred_venues' => 'json',
        'preferred_caterers' => 'json',
        'preferred_decorators' => 'json',
        'preferred_photographers' => 'json',
        'vendor_contacts' => 'json',
        'important_dates' => 'json',
        'checklist_template' => 'json',
        'typical_budget' => 'decimal:2',
        'is_event_planner' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmEventProfile[id=%d, style=%s]', $this->id ?? 0, $this->event_style ?? '');
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Boot методы модели — global scopes и auto-UUID.
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model): void {
            if (! $model->uuid && $model->isFillable('uuid')) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
