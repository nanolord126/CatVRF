<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Payment\Domain\Entities\Payout;

class PayoutModel extends Model
{
    use HasFactory;

    protected $table = 'payouts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'seller_id',
        'payment_record_id',
        'amount_kopecks',
        'provider_code',
        'status',
        'provider_payout_id',
        'provider_response',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'metadata' => 'array',
        'processed_at' => 'immutable_datetime',
        'failed_at' => 'immutable_datetime',
    ];

    public function toDomain(): Payout
    {
        return new Payout(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            sellerId: $this->seller_id,
            paymentRecordId: $this->payment_record_id,
            amountKopecks: $this->amount_kopecks,
            providerCode: $this->provider_code,
            status: $this->status,
            providerPayoutId: $this->provider_payout_id,
            providerResponse: $this->provider_response,
            correlationId: $this->correlation_id,
            metadata: $this->metadata,
            createdAt: $this->created_at->toImmutable(),
            updatedAt: $this->updated_at->toImmutable(),
            processedAt: $this->processed_at,
            failedAt: $this->failed_at,
        );
    }
}
