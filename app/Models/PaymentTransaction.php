<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'uuid',
        'wallet_id',
        'tenant_id',
        'user_id',
        'payment_id',
        'idempotency_key',
        'provider',
        'provider_code',
        'provider_payment_id',
        'status',
        'payment_method',
        'amount',
        'currency',
        'hold_amount',
        'hold',
        'authorized_at',
        'captured_at',
        'refunded_at',
        'failed_at',
        'correlation_id',
        'ip_address',
        'device_fingerprint',
        'fraud_score',
        'fraud_ml_version',
        'ml_fraud_version',
        'three_ds_required',
        'three_ds_verified',
        'metadata',
        'meta',
        'tags',
    ];

    protected $casts = [
        'amount' => 'integer',
        'hold_amount' => 'integer',
        'hold' => 'boolean',
        'three_ds_required' => 'boolean',
        'three_ds_verified' => 'boolean',
        'fraud_score' => 'float',
        'metadata' => 'json',
        'meta' => 'json',
        'tags' => 'json',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'refunded_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_AUTHORIZED = 'authorized';
    const STATUS_CAPTURED = 'captured';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Связь с кошельком
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'id');
    }

    /**
     * Скоп для авторизованных платежей
     */
    public function scopeAuthorized($query)
    {
        return $query->where('status', self::STATUS_AUTHORIZED);
    }

    /**
     * Скоп для захватанных платежей
     */
    public function scopeCaptured($query)
    {
        return $query->where('status', self::STATUS_CAPTURED);
    }

    /**
     * Скоп для возвращённых платежей
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
