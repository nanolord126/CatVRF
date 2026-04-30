<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * CrmPipeline — воронка продаж/услуг.
 * Определяет этапы (Stages) и привязана к tenant и вертикали.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmPipeline extends Model
{
    use TenantScoped, SoftDeletes;

    protected $table = 'crm_pipelines';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'name',
        'slug',
        'vertical',
        'description',
        'is_default',
        'is_active',
        'color',
        'icon',
        'order',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'metadata' => 'json',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForVertical(Builder $query, string $vertical): Builder
    {
        return $query->where('vertical', $vertical);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(CrmStage::class, 'pipeline_id')
            ->orderBy('order');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'pipeline_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
