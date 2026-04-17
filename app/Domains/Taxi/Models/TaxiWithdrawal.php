<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class TaxiWithdrawal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taxi_withdrawals';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'wallet_id',
        'driver_id',
        'amount_kopeki',
        'currency',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'bic',
        'inn',
        'kpp',
        'processing_fee_kopeki',
        'net_amount_kopeki',
        'requested_at',
        'processed_at',
        'failed_at',
        'failure_reason',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'amount_kopeki' => 'integer',
        'processing_fee_kopeki' => 'integer',
        'net_amount_kopeki' => 'integer',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Статусы вывода средств.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected static function booted(): void
    {
        static::creating(function (TaxiWithdrawal $withdrawal) {
            $withdrawal->uuid = $withdrawal->uuid ?? (string) Str::uuid();
            $withdrawal->tenant_id = $withdrawal->tenant_id ?? (tenant()->id ?? 1);
            $withdrawal->status = $withdrawal->status ?? self::STATUS_PENDING;
            $withdrawal->requested_at = $withdrawal->requested_at ?? now();
            $withdrawal->correlation_id = $withdrawal->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Отношения.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(TaxiDriverWallet::class, 'wallet_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /**
     * Получить сумму в рублях.
     */
    public function getAmountInRubles(): float
    {
        return $this->amount_kopeki / 100;
    }

    /**
     * Получить чистую сумму в рублях.
     */
    public function getNetAmountInRubles(): float
    {
        return $this->net_amount_kopeki / 100;
    }

    /**
     * Получить комиссию за обработку в рублях.
     */
    public function getProcessingFeeInRubles(): float
    {
        return $this->processing_fee_kopeki / 100;
    }

    /**
     * Проверить, ожидает ли обработки.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Проверить, обрабатывается ли.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Проверить, завершен ли вывод.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Пометить как обрабатываемый.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Пометить как завершенный.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Пометить как неудачный.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Отменить вывод.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
