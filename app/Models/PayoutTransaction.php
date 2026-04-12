<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Транзакция выплаты (курьерам, мастерам, партнёрам)
 *
 * @package App\Models
 */
final class PayoutTransaction extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'payout_transactions';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'wallet_id',
        'recipient_id',
        'recipient_type',
        'amount',
        'status',
        'provider_code',
        'provider_response',
        'paid_at',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_response' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
