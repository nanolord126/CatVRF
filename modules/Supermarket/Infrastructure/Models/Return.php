<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supermarket\Domain\Enums\ReturnStatus;
use Modules\Supermarket\Domain\Enums\ReturnReason;

class Return extends Model
{
    protected $table = 'supermarket_returns';

    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'status',
        'reason_type',
        'reason_comment',
        'total_amount',
        'refund_amount',
        'is_cold_chain',
        'return_method',
        'images',
        'approved_at',
        'completed_at',
        'auto_approved',
        'reject_reason',
    ];

    protected $casts = [
        'images' => 'array',
        'is_cold_chain' => 'boolean',
        'auto_approved' => 'boolean',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(\Modules\Supermarket\Infrastructure\Models\SupermarketOrder::class, 'order_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', ReturnStatus::PENDING->value);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ReturnStatus::APPROVED->value);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ReturnStatus::REJECTED->value);
    }

    public function scopeColdChain($query)
    {
        return $query->where('is_cold_chain', true);
    }

    public function getStatusEnum(): ReturnStatus
    {
        return ReturnStatus::from($this->status);
    }

    public function getReasonTypeEnum(): ReturnReason
    {
        return ReturnReason::from($this->reason_type);
    }

    public function canBeApproved(): bool
    {
        return $this->getStatusEnum()->canBeApproved();
    }

    public function canBeRejected(): bool
    {
        return $this->getStatusEnum()->canBeRejected();
    }

    public function isFinal(): bool
    {
        return $this->getStatusEnum()->isFinal();
    }
}
