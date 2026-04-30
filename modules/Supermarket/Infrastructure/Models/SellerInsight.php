<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerInsight extends Model
{
    protected $table = 'supermarket_seller_insights';

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
        'actionable' => 'boolean',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByImpact($query, string $impact)
    {
        return $query->where('impact', $impact);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeHighImpact($query)
    {
        return $query->where('impact', 'high');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'revenue_growth' => 'Рост выручки',
            'top_product' => 'Топ товар',
            'pricing_recommendation' => 'Рекомендация по цене',
            'return_problem' => 'Проблема с возвратами',
            'category_performance' => 'Категория',
            'general' => 'Общий',
            default => 'Другое',
        };
    }
}
