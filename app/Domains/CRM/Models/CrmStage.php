<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use App\Models\BusinessGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * CrmStage — этап воронки.
 * Определяет статус сделки (Lead, Qualification, Booking и т.д.).
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmStage extends Model
{
    use TenantScoped;

    protected $table = 'crm_stages';

    protected $fillable = [
        'tenant_id',
        'pipeline_id',
        'uuid',
        'name',
        'key',
        'description',
        'order',
        'color',
        'probability',
        'is_final',
        'is_won',
        'is_lost',
        'auto_transition_rules',
        'time_limit_hours',
    ];

    protected $casts = [
        'order' => 'integer',
        'probability' => 'integer',
        'is_final' => 'boolean',
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
        'auto_transition_rules' => 'json',
        'time_limit_hours' => 'integer',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    public function scopeFinal(Builder $query): Builder
    {
        return $query->where('is_final', true);
    }

    public function scopeWon(Builder $query): Builder
    {
        return $query->where('is_won', true);
    }

    public function scopeLost(Builder $query): Builder
    {
        return $query->where('is_lost', true);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'stage_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
