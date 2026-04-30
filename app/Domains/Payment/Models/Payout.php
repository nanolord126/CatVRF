<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use App\Domains\Payment\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BusinessGroup;
use App\Models\Tenant;
use App\Models\User;

/**
 * Выплата продавцу.
 *
 * Трекинг выплат от платформы продавцам после успешного платежа.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property int $seller_id
 * @property int $payment_record_id
 * @property string $uuid
 * @property int $amount_kopecks
 * @property PaymentProvider $provider_code
 * @property string $status
 * @property string|null $provider_payout_id
 * @property array|null $provider_response
 * @property string|null $correlation_id
 * @property array|null $metadata
 */
final class Payout extends Model
{
    use TenantScoped;

    protected $table = 'payouts';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'seller_id',
        'payment_record_id',
        'uuid',
        'amount_kopecks',
        'provider_code',
        'status',
        'provider_payout_id',
        'provider_response',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'provider_code' => PaymentProvider::class,
        'amount_kopecks' => 'integer',
        'provider_response' => 'json',
        'metadata' => 'json',
    ];

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * @return BelongsTo<BusinessGroup, self>
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    /**
     * @return BelongsTo<User, self>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * @return BelongsTo<PaymentRecord, self>
     */
    public function paymentRecord(): BelongsTo
    {
        return $this->belongsTo(PaymentRecord::class, 'payment_record_id');
    }

    /**
     * Сумма в рублях (для отображения).
     */
    public function getAmountRublesAttribute(): float
    {
        return $this->amount_kopecks / 100;
    }

    /**
     * Auto-UUID.
     */
    protected static function booted(): void
    {
        self::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
