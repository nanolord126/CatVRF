<?php

declare(strict_types=1);

namespace Modules\Wallet\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent-модель транзакции (поверх таблицы bavix transactions).
 */
final class WalletTransactionModel extends Model
{
    use SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'payable_type',
        'payable_id',
        'wallet_id',
        'type',
        'amount',
        'confirmed',
        'meta',
        'uuid',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'meta'      => 'json',
        'amount'    => 'integer',
        'confirmed' => 'boolean',
    ];
}
