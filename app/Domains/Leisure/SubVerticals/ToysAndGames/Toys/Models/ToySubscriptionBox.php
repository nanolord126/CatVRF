<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\ToysAndGames\Toys\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * ToySubscriptionBox Model (L1)
 * Monthly recurring revenue / rotation model.
 */
final class ToySubscriptionBox extends Model
{
    use TenantScoped;
    use ToysDomainTrait;

    protected $table = 'toy_subscription_boxes';

    protected $fillable = [
        'uuid', 'tenant_id', 'user_id', 'age_group_id',
        'monthly_limit', 'status', 'is_paid',
        'next_delivery_at', 'last_sent_at', 'metadata', 'correlation_id',
    ];

    protected $casts = [
        'metadata' => 'json',
        'is_paid' => 'boolean',
        'next_delivery_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class, 'age_group_id');
    }
}
