<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BusinessGroup;
use App\Models\Tenant;

/**
 * Фискальный документ (54-ФЗ).
 *
 * Хранит данные о фискализированных чеках для соответствия 54-ФЗ.
 * Связан с PaymentRecord и отправляется в ОФД автоматически.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property int $payment_record_id
 * @property string $uuid
 * @property string $ofd_provider
 * @property string $receipt_type
 * @property string $fiscal_sign
 * @property int $fiscal_document_number
 * @property int $fiscal_document_attribute
 * @property string $status
 * @property int $amount_kopecks
 * @property string $taxation_type
 * @property array|null $receipt_data
 * @property array|null $ofd_response
 * @property string|null $sent_at
 * @property string|null $received_at
 * @property string $correlation_id
 * @property array|null $metadata
 */
final class FiscalDocument extends Model
{
    use TenantScoped;

    protected $table = 'fiscal_documents';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'payment_record_id',
        'uuid',
        'ofd_provider',
        'receipt_type',
        'fiscal_sign',
        'fiscal_document_number',
        'fiscal_document_attribute',
        'status',
        'amount_kopecks',
        'taxation_type',
        'receipt_data',
        'ofd_response',
        'sent_at',
        'received_at',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'amount_kopecks' => 'integer',
        'fiscal_document_number' => 'integer',
        'fiscal_document_attribute' => 'integer',
        'receipt_data' => 'json',
        'ofd_response' => 'json',
        'metadata' => 'json',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
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
     * Является ли чек успешно получен ОФД.
     */
    public function isReceived(): bool
    {
        return $this->status === 'received' && $this->received_at !== null;
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
