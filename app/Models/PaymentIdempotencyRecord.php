<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;

final class PaymentIdempotencyRecord extends Model
{
    public const OPERATION_INIT_PAYMENT = 'init_payment';

    public const OPERATION_CAPTURE = 'capture';

    public const OPERATION_REFUND = 'refund';

    public const OPERATION_PAYOUT = 'payout';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'payment_idempotency_records';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'operation',
        'idempotency_key',
        'merchant_id',
        'payload_hash',
        'response_data',
        'status',
        'expires_at',
        'correlation_id',
        'tenant_id',
    ];

    protected $casts = [
        'response_data' => 'json',
        'expires_at' => 'datetime',
    ];

    /**
     * Найти по idempotency_key
     */
    public static function findByKey(string $idempotencyKey)
    {
        return self::where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * Проверить если запрос уже обработан
     */
    public static function isProcessed(string $idempotencyKey): bool
    {
        $record = self::findByKey($idempotencyKey);

        return $record && $record->status === self::STATUS_COMPLETED;
    }

    /**
     * Скоп для только активных записей (не истёкших)
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', CarbonImmutable::now());
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
