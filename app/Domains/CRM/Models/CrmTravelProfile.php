<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmTravelProfile — CRM-профиль клиента вертикали Путешествия/Туризм.
 *
 * Паспорт, визы, предпочтения, история поездок, программы лояльности.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmTravelProfile extends Model
{

    protected $table = 'crm_travel_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'passport_country', 'passport_expires_at',
        'visas', 'travel_preferences', 'preferred_destinations', 'visited_countries',
        'trip_history', 'preferred_airline', 'preferred_hotel_chain', 'seat_preference',
        'meal_preference', 'loyalty_programs', 'travel_companions', 'needs_transfer',
        'needs_insurance', 'avg_trip_budget', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'visas' => 'json',
        'travel_preferences' => 'json',
        'preferred_destinations' => 'json',
        'visited_countries' => 'json',
        'trip_history' => 'json',
        'loyalty_programs' => 'json',
        'travel_companions' => 'json',
        'passport_expires_at' => 'date',
        'needs_transfer' => 'boolean',
        'needs_insurance' => 'boolean',
        'avg_trip_budget' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmTravelProfile[id=%d]', $this->id ?? 0);
    }

    /**
     * Boot методы модели — global scopes и auto-UUID.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model): void {
            if (!$model->uuid && $model->isFillable('uuid')) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
