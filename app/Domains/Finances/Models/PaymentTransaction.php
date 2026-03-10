<?php

namespace App\Domains\Finances\Models;

use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'payment_transactions';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'splits' => 'array',
        'metadata' => 'array',
        'captured_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'payment_id',
        'user_id',
        'tenant_id',
        'amount',
        'status',
        'splits',
        'metadata',
        'correlation_id',
        'captured_at',
    ];

    // Статусы платежа
    public const STATUS_PENDING = 'pending';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Связь с пользователем.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Определить, является ли платёж успешным.
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_SETTLED, self::STATUS_AUTHORIZED]);
    }

    /**
     * Обновить статус с логированием.
     */
    public function updateStatus(string $newStatus, array $metadata = []): bool
    {
        if (!in_array($newStatus, [self::STATUS_PENDING, self::STATUS_AUTHORIZED, self::STATUS_SETTLED, self::STATUS_FAILED, self::STATUS_REFUNDED])) {
            throw new \InvalidArgumentException("Invalid payment status: {$newStatus}");
        }

        $this->status = $newStatus;
        if (!empty($metadata)) {
            $this->metadata = array_merge($this->metadata ?? [], $metadata);
        }

        return $this->save();
    }
}
