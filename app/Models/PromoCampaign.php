<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Промо-кампания (alias App\Domains\PromoCampaigns\Models\PromoCampaign)
 *
 * @package App\Models
 */
final class PromoCampaign extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'promo_campaigns';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'name',
        'code',
        'type',
        'status',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_uses',
        'max_uses_per_user',
        'budget_kopecks',
        'starts_at',
        'ends_at',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'discount_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
