<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

/**
 * Реферальное вознаграждение (alias App\Domains\Referral\Models\ReferralReward)
 *
 * @package App\Models
 */
final class ReferralReward extends Model
{

    protected $table = 'referral_rewards';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'referral_id',
        'recipient_id',
        'amount',
        'type',
        'status',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
