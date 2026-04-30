<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

final class SellerInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',
        'title',
        'description',
        'value',
        'impact',
        'actionable',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'float',
        'impact' => 'string',
        'actionable' => 'boolean',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeHighImpact(Builder $query): Builder
    {
        return $query->where('impact', 'high');
    }

    public function scopeMediumImpact(Builder $query): Builder
    {
        return $query->where('impact', 'medium');
    }

    public function scopeActionable(Builder $query): Builder
    {
        return $query->where('actionable', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    public function isHighImpact(): bool
    {
        return $this->impact === 'high';
    }

    public function isMediumImpact(): bool
    {
        return $this->impact === 'medium';
    }

    public function isLowImpact(): bool
    {
        return $this->impact === 'low';
    }

    public function getImpactColor(): string
    {
        return match ($this->impact) {
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function getImpactLabel(): string
    {
        return match ($this->impact) {
            'high' => 'Высокий',
            'medium' => 'Средний',
            'low' => 'Низкий',
            default => 'Неизвестно',
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'revenue_growth' => 'Рост выручки',
            'revenue_decline' => 'Падение выручки',
            'top_product' => 'Топ-товар',
            'high_returns' => 'Высокий процент возвратов',
            'b2b_opportunity' => 'B2B потенциал',
            'pricing_recommendation' => 'Рекомендация по цене',
            'stock_warning' => 'Предупреждение о запасах',
            'customer_retention' => 'Удержание клиентов',
            default => 'Общий',
        };
    }

    public function markAsRead(): void
    {
        // Could add a read_at field if needed
    }

    public function dismiss(): void
    {
        $this->update(['expires_at' => now()]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'title' => $this->title,
            'description' => $this->description,
            'value' => $this->value,
            'impact' => $this->impact,
            'impact_label' => $this->getImpactLabel(),
            'impact_color' => $this->getImpactColor(),
            'actionable' => $this->actionable,
            'is_active' => $this->isActive(),
            'generated_at' => $this->generated_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
