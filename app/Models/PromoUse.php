<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Факт применения промокода (alias App\Domains\PromoCampaigns\Models\PromoUse)
 *
 * @package App\Models
 */
final class PromoUse extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'promo_uses';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'promo_campaign_id',
        'user_id',
        'order_id',
        'discount_applied',
        'used_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'discount_applied' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
