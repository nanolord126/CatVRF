<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent Model для таблицы payment_transactions_v2.
 * НЕ используется напрямую в бизнес-логике — только через Repository.
 */
final class PaymentModel extends Model
{
    use SoftDeletes;

    protected $table = 'payment_transactions_v2';

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'amount',
        'currency',
        'idempotency_key',
        'status',
        'provider_payment_id',
        'payment_url',
        'correlation_id',
        'metadata',
        'recurring',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'id'        => 'string',
        'amount'    => 'integer',
        'metadata'  => 'array',
        'tags'      => 'array',
        'recurring' => 'boolean',
    ];

    public $incrementing = false;

    protected $keyType = 'string';
}
