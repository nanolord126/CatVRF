<?php

declare(strict_types=1);

namespace Modules\Wallet\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent-модель кошелька (поверх таблицы bavix wallets).
 * Используется исключительно внутри Infrastructure-слоя.
 */
final class WalletModel extends Model
{
    protected $table = 'wallets';

    protected $fillable = [
        'holder_type',
        'holder_id',
        'name',
        'slug',
        'uuid',
        'description',
        'meta',
        'balance',
        'decimal_places',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'meta'           => 'json',
        'balance'        => 'integer',
        'decimal_places' => 'integer',
    ];
}
