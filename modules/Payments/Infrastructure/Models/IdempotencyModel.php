<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Model для таблицы payment_idempotency_records_v2.
 */
final class IdempotencyModel extends Model
{
    protected $table = 'payment_idempotency_records_v2';

    protected $fillable = [
        'tenant_id',
        'operation',
        'idempotency_key',
        'payload_hash',
        'response_data',
        'expires_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'expires_at'    => 'datetime',
    ];
}
