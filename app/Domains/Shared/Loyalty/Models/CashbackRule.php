<?php

declare(strict_types=1);

namespace App\Domains\Shared\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CashbackRule extends Model
{
    protected $table = 'cashback_rules';

    protected $fillable = [
        'tenant_id',
        'vertical',
        'sub_vertical',
        'percent',
        'min_order_amount',
        'max_cashback_amount',
        'is_active',
        'moderation_status',
        'conditions',
    ];

    protected $casts = [
        'percent' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_cashback_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
