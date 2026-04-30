<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Payment\Domain\Entities\Payment;
use Modules\Payment\Domain\Enums\PaymentStatus;

class PaymentModel extends Model
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'payable_id',
        'payable_type',
        'uuid',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'error_message',
        'paid_at',
        'failed_at',
        'refunded_at',
        'refund_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function toDomain(): Payment
    {
        return new Payment(
            id: $this->id,
            tenantId: $this->tenant_id,
            payableId: $this->payable_id,
            payableType: $this->payable_type,
            uuid: $this->uuid,
            amount: $this->amount,
            currency: $this->currency,
            status: PaymentStatus::from($this->status),
            gateway: $this->gateway,
            gatewayTransactionId: $this->gateway_transaction_id,
            gatewayResponse: $this->gateway_response,
            errorMessage: $this->error_message,
            paidAt: $this->paid_at ? \Carbon\CarbonImmutable::parse($this->paid_at) : null,
            failedAt: $this->failed_at ? \Carbon\CarbonImmutable::parse($this->failed_at) : null,
            refundedAt: $this->refunded_at ? \Carbon\CarbonImmutable::parse($this->refunded_at) : null,
            refundReason: $this->refund_reason,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(Payment $payment): self
    {
        return new self([
            'id' => $payment->id,
            'tenant_id' => $payment->tenantId,
            'payable_id' => $payment->payableId,
            'payable_type' => $payment->payableType,
            'uuid' => $payment->uuid,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status->value,
            'gateway' => $payment->gateway,
            'gateway_transaction_id' => $payment->gatewayTransactionId,
            'gateway_response' => $payment->gatewayResponse,
            'error_message' => $payment->errorMessage,
            'paid_at' => $payment->paidAt?->toDateTimeString(),
            'failed_at' => $payment->failedAt?->toDateTimeString(),
            'refunded_at' => $payment->refundedAt?->toDateTimeString(),
            'refund_reason' => $payment->refundReason,
            'metadata' => $payment->metadata,
        ]);
    }
}
