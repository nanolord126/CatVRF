<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PaymentIdempotencyRecord extends Model
{
    protected $table = 'payment_idempotency_records';

    protected $fillable = [
        'operation',
        'idempotency_key',
        'merchant_id',
        'payload_hash',
        'response_data',
        'status',
        'expires_at',
        'correlation_id',
    ];

    protected $casts = [
        'response_data' => 'json',
        'expires_at' => 'datetime',
    ];

    const OPERATION_INIT_PAYMENT = 'init_payment';
    const OPERATION_CAPTURE = 'capture';
    const OPERATION_REFUND = 'refund';
    const OPERATION_PAYOUT = 'payout';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

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
        return $query->where('expires_at', '>', now());
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
