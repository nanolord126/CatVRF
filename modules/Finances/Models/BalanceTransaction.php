<?php

declare(strict_types=1);

namespace Modules\Finances\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finances\Enums\BalanceTransactionStatus;
use Modules\Finances\Enums\BalanceTransactionType;

final class BalanceTransaction extends Model
{
    use HasFactory;

    protected $table = 'balance_transactions';

    protected $fillable = [
        'uuid',
        'wallet_id',
        'type',
        'status',
        'amount',
        'correlation_id',
        'meta',
    ];

    protected $casts = [
        'type' => BalanceTransactionType::class,
        'status' => BalanceTransactionStatus::class,
        'amount' => 'integer',
        'meta' => 'json',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
