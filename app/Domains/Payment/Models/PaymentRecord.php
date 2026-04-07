<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Платёжная транзакция — запись о внешнем платеже через шлюз.
 *
 * @property int             $id
 * @property int             $tenant_id
 * @property int|null        $business_group_id
 * @property string          $uuid
 * @property string          $idempotency_key
 * @property PaymentProvider $provider_code
 * @property PaymentStatus   $status
 * @property int             $amount_kopecks
 * @property bool            $is_hold
 * @property string|null     $provider_payment_id
 * @property array|null      $provider_response
 * @property string|null     $correlation_id
 * @property array|null      $tags
 * @property array|null      $metadata
 */
final class PaymentRecord extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'idempotency_key',
        'provider_code',
        'status',
        'amount_kopecks',
        'is_hold',
        'provider_payment_id',
        'provider_response',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'provider_code' => PaymentProvider::class,
        'status' => PaymentStatus::class,
        'amount_kopecks' => 'integer',
        'is_hold' => 'boolean',
        'provider_response' => 'json',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    /**
     * Tenant-scoping + auto-UUID.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * @return BelongsTo<Model, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * @return BelongsTo<Model, self>
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    /**
     * Сумма в рублях (для отображения).
     */
    public function getAmountRublesAttribute(): float
    {
        return $this->amount_kopecks / 100;
    }

    /**
     * Можно ли перевести платёж в указанный статус.
     */
    public function canTransitionTo(PaymentStatus $target): bool
    {
        return $this->status->canTransitionTo($target);
    }

    /**
     * Является ли платёж завершённым.
     */
    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }
}
