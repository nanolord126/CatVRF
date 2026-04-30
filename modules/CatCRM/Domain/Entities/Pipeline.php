<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;

/**
 * Pipeline — Воронка продаж/услуг в CRM
 * 
 * Представляет настраиваемую воронку для конкретной вертикали бизнеса.
 * Каждая воронка состоит из этапов (Stage), через которые проходят сделки (Deal).
 */
final class Pipeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'name',
        'slug',
        'description',
        'vertical',
        'is_default',
        'is_active',
        'color',
        'icon',
        'order',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'metadata' => 'json',
    ];

    protected $table = 'crm_pipelines';

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * Tenant (бизнес-владелец воронки)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Business Group (филиал, если применимо)
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    /**
     * Этапы воронки
     */
    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('order');
    }

    /**
     * Сделки в этой воронке
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByVertical($query, string $vertical)
    {
        return $query->where('vertical', $vertical);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBusinessGroup($query, ?int $businessGroupId)
    {
        if ($businessGroupId === null) {
            return $query->whereNull('business_group_id');
        }

        return $query->where('business_group_id', $businessGroupId);
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Получить первый этап воронки
     */
    public function getFirstStage(): ?Stage
    {
        return $this->stages()->orderBy('order')->first();
    }

    /**
     * Получить последний этап воронки
     */
    public function getLastStage(): ?Stage
    {
        return $this->stages()->orderByDesc('order')->first();
    }

    /**
     * Получить этап по имени
     */
    public function getStageByName(string $name): ?Stage
    {
        return $this->stages()->where('name', $name)->first();
    }

    /**
     * Получить статистику по воронке
     */
    public function getStatistics(): array
    {
        $deals = $this->deals;
        
        return [
            'total_deals' => $deals->count(),
            'total_value' => $deals->sum('value'),
            'by_stage' => $this->stages()->withCount('deals')->get()->pluck('deals_count', 'id')->toArray(),
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            $model->slug ??= \Illuminate\Support\Str::slug($model->name);
        });
    }
}
